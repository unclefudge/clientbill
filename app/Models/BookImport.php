<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookImport extends Model
{
    protected $fillable = [
        'book_business_id',
        'book_bank_account_id',
        'type',
        'status',
        'original_filename',
        'disk',
        'file_path',
        'format_snapshot',
        'period_start',
        'period_end',
        'row_count',
        'included_count',
        'excluded_count',
        'duplicate_count',
        'imported_at',
    ];

    protected function casts(): array
    {
        return [
            'format_snapshot' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'imported_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BookBusiness::class, 'book_business_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BookBankAccount::class, 'book_bank_account_id');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(BookImportRow::class);
    }
}
