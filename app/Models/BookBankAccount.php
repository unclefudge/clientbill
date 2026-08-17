<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookBankAccount extends Model
{
    protected $fillable = [
        'book_business_id',
        'name',
        'institution',
        'account_name',
        'last_four',
        'import_format',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'import_format' => 'array',
            'active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BookBusiness::class, 'book_business_id');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(BookImport::class, 'book_bank_account_id');
    }
}
