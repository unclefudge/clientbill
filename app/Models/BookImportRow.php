<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookImportRow extends Model
{
    protected $fillable = [
        'book_import_id',
        'book_transaction_id',
        'suggested_book_category_id',
        'book_category_id',
        'row_number',
        'transaction_date',
        'amount',
        'balance',
        'description',
        'fingerprint',
        'status',
        'confirmed_at',
        'gst_treatment',
        'gst_amount',
        'business_use_percentage',
        'purchase_type',
        'suggestion_confidence',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'amount' => 'decimal:2',
            'balance' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'gst_amount' => 'decimal:2',
            'business_use_percentage' => 'decimal:2',
            'raw_data' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(BookImport::class, 'book_import_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(BookTransaction::class, 'book_transaction_id');
    }

    public function suggestedCategory(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'suggested_book_category_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'book_category_id');
    }
}
