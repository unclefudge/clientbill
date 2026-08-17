<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class BookDocument extends Model
{
    protected $fillable = [
        'book_business_id',
        'book_financial_year_id',
        'book_bas_period_id',
        'book_import_id',
        'book_transaction_id',
        'document_type',
        'period',
        'original_filename',
        'disk',
        'path',
        'mime_type',
        'size',
        'uploaded_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BookBusiness::class, 'book_business_id');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(BookFinancialYear::class, 'book_financial_year_id');
    }

    public function basPeriod(): BelongsTo
    {
        return $this->belongsTo(BookBasPeriod::class, 'book_bas_period_id');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(BookImport::class, 'book_import_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(BookTransaction::class, 'book_transaction_id');
    }

    protected static function booted(): void
    {
        static::deleting(function (BookDocument $document): void {
            if (! $document->disk || ! $document->path) {
                return;
            }

            try {
                Storage::disk($document->disk)->delete($document->path);
            } catch (\Throwable $exception) {
                // Do not block database housekeeping if remote storage is
                // temporarily unavailable. Laravel will still report/log it.
                report($exception);
            }
        });
    }
}
