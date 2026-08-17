<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookBusiness extends Model
{
    protected $fillable = [
        'name',
        'legal_name',
        'abn',
        'gst_registered',
        'gst_rate',
        'bas_frequency',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'gst_registered' => 'boolean',
            'gst_rate' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_business_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function financialYears(): HasMany
    {
        return $this->hasMany(BookFinancialYear::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BookBankAccount::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(BookCategory::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BookTransaction::class);
    }

    public function imports(): HasMany
    {
        return $this->hasMany(BookImport::class);
    }

    public function basPeriods(): HasMany
    {
        return $this->hasMany(BookBasPeriod::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(BookDocument::class);
    }
}
