<?php

namespace App\Services\Books;

use App\Models\BookDocument;
use App\Models\BookFinancialYear;
use App\Models\BookTransaction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookDocumentStorage
{
    public function spacesConfigured(): bool
    {
        $disk = config('filesystems.disks.books', []);

        return filled($disk['key'] ?? null)
            && filled($disk['secret'] ?? null)
            && filled($disk['bucket'] ?? null)
            && filled($disk['endpoint'] ?? null);
    }

    public function ready(): bool
    {
        return $this->spacesConfigured() || app()->environment(['local', 'testing']);
    }

    public function diskName(): string
    {
        if ($this->spacesConfigured()) {
            return 'books';
        }

        // Local development stays private and testable without requiring a
        // real Spaces bucket. Production never silently falls back to the
        // application server for financial documents.
        if (app()->environment(['local', 'testing'])) {
            return 'local';
        }

        throw ValidationException::withMessages([
            'documentUploads' => 'DigitalOcean Spaces is not configured for Books. Set the BOOKS_SPACES_* values before uploading financial documents.',
        ]);
    }

    public function storageLabel(): string
    {
        if ($this->spacesConfigured()) {
            return 'Private DigitalOcean Spaces';
        }

        if (app()->environment(['local', 'testing'])) {
            return 'Private local storage (development)';
        }

        return 'DigitalOcean Spaces setup required';
    }

    public function storeTransactionDocument(
        BookTransaction $transaction,
        UploadedFile $file,
        ?string $notes = null,
    ): BookDocument {
        $disk = $this->diskName();
        $extension = strtolower($file->getClientOriginalExtension() ?: '');

        if ($extension === '') {
            $extension = match ($file->getMimeType()) {
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'bin',
            };
        }

        $directory = sprintf(
            'books/%d/transactions/%d',
            $transaction->book_business_id,
            $transaction->id,
        );

        $storedName = Str::uuid()->toString() . '.' . $extension;
        $storedPath = $file->storeAs($directory, $storedName, $disk);

        if (! $storedPath) {
            throw ValidationException::withMessages([
                'documentUploads' => 'The document could not be stored. Please try again.',
            ]);
        }

        $financialYear = BookFinancialYear::query()
            ->where('book_business_id', $transaction->book_business_id)
            ->whereDate('start_date', '<=', $transaction->transaction_date)
            ->whereDate('end_date', '>=', $transaction->transaction_date)
            ->first();

        try {
            return BookDocument::create([
                'book_business_id' => $transaction->book_business_id,
                'book_financial_year_id' => $financialYear?->id,
                'book_transaction_id' => $transaction->id,
                'document_type' => 'transaction_source',
                'period' => $transaction->transaction_date->toDateString(),
                'original_filename' => $file->getClientOriginalName(),
                'disk' => $disk,
                'path' => $storedPath,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_at' => now('Australia/Hobart'),
                'notes' => filled($notes) ? trim($notes) : null,
            ]);
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($storedPath);
            throw $exception;
        }
    }

    public function temporaryUrl(BookDocument $document): string
    {
        if (! $document->disk || ! $document->path) {
            throw ValidationException::withMessages([
                'document' => 'This document does not have a stored file.',
            ]);
        }

        $disk = Storage::disk($document->disk);

        if (! $disk->exists($document->path)) {
            throw ValidationException::withMessages([
                'document' => 'The stored file could not be found.',
            ]);
        }

        $expires = now()->addMinutes(10);

        // Laravel's private S3/Spaces disk generates a signed URL here. The
        // local disk in Laravel 12 is configured with "serve" => true, so
        // local development also gets a temporary signed URL.
        if ($document->disk === 'books') {
            return $disk->temporaryUrl(
                $document->path,
                $expires,
                [
                    'ResponseContentType' => $document->mime_type ?: 'application/octet-stream',
                    'ResponseContentDisposition' => 'inline; filename="' . $this->safeHeaderFilename($document->original_filename) . '"',
                ],
            );
        }

        return $disk->temporaryUrl($document->path, $expires);
    }

    public function delete(BookDocument $document): void
    {
        $document->delete();
    }

    protected function safeHeaderFilename(string $filename): string
    {
        return str_replace(['"', "\r", "\n"], ['', '', ''], $filename);
    }
}
