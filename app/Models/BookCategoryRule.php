<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookCategoryRule extends Model
{
    protected $fillable = [
        'book_business_id',
        'book_category_id',
        'match_type',
        'match_value',
        'gst_treatment',
        'business_use_percentage',
        'times_confirmed',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'business_use_percentage' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BookBusiness::class, 'book_business_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'book_category_id');
    }
}
