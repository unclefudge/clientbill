<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class BookTransaction extends Model
{
    protected $fillable = [
        'book_business_id',
        'book_bank_account_id',
        'book_category_id',
        'book_import_id',
        'transaction_date',
        'amount',
        'business_use_percentage',
        'business_amount',
        'net_amount',
        'gst_amount',
        'gst_treatment',
        'sale_type',
        'purchase_type',
        'source',
        'payment_source',
        'description',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'business_use_percentage' => 'decimal:2',
            'business_amount' => 'decimal:4',
            'net_amount' => 'decimal:2',
            'gst_amount' => 'decimal:4',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (BookTransaction $transaction): void {
            if ($basPeriod = BookBasPeriod::lodgedForBusinessDate(
                (int) $transaction->book_business_id,
                $transaction->transaction_date,
            )) {
                throw ValidationException::withMessages([
                    'transaction' => $basPeriod->period_label . ' BAS is lodged. Reopen that BAS period before adding a transaction to it.',
                ]);
            }
        });

        static::updating(function (BookTransaction $transaction): void {
            $originalBusinessId = (int) ($transaction->getOriginal('book_business_id') ?: $transaction->book_business_id);
            $originalDate = $transaction->getOriginal('transaction_date') ?: $transaction->transaction_date;

            if ($financialYear = BookFinancialYear::closedForBusinessDate($originalBusinessId, $originalDate)) {
                throw ValidationException::withMessages([
                    'transaction' => $financialYear->label . ' is closed. Reopen the financial year before changing this transaction.',
                ]);
            }

            if ($basPeriod = BookBasPeriod::lodgedForBusinessDate($originalBusinessId, $originalDate)) {
                throw ValidationException::withMessages([
                    'transaction' => $basPeriod->period_label . ' BAS is lodged. Reopen that BAS period before changing this transaction.',
                ]);
            }

            if ($transaction->isDirty('transaction_date') || $transaction->isDirty('book_business_id')) {
                if ($financialYear = BookFinancialYear::closedForBusinessDate(
                    (int) $transaction->book_business_id,
                    $transaction->transaction_date,
                )) {
                    throw ValidationException::withMessages([
                        'transaction' => $financialYear->label . ' is closed. A transaction cannot be moved into a closed financial year.',
                    ]);
                }

                if ($basPeriod = BookBasPeriod::lodgedForBusinessDate(
                    (int) $transaction->book_business_id,
                    $transaction->transaction_date,
                )) {
                    throw ValidationException::withMessages([
                        'transaction' => $basPeriod->period_label . ' BAS is lodged. A transaction cannot be moved into that period until it is reopened.',
                    ]);
                }
            }
        });

        static::deleting(function (BookTransaction $transaction): void {
            if ($financialYear = BookFinancialYear::closedForBusinessDate(
                (int) $transaction->book_business_id,
                $transaction->transaction_date,
            )) {
                throw ValidationException::withMessages([
                    'transaction' => $financialYear->label . ' is closed. Reopen the financial year before deleting this transaction.',
                ]);
            }

            if ($basPeriod = BookBasPeriod::lodgedForBusinessDate(
                (int) $transaction->book_business_id,
                $transaction->transaction_date,
            )) {
                throw ValidationException::withMessages([
                    'transaction' => $basPeriod->period_label . ' BAS is lodged. Reopen that BAS period before deleting this transaction.',
                ]);
            }

            // Delete through the models so BookDocument's deleting event can
            // remove the corresponding private file from storage.
            $transaction->documents()->get()->each->delete();
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BookBusiness::class, 'book_business_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BookBankAccount::class, 'book_bank_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'book_category_id');
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(BookImport::class, 'book_import_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BookDocument::class, 'book_transaction_id')
            ->orderByDesc('uploaded_at')
            ->orderByDesc('id');
    }
}
