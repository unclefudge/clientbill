<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookBasPeriod extends Model
{
    protected $fillable = [
        'book_business_id',
        'book_financial_year_id',
        'period_type',
        'period_label',
        'start_date',
        'end_date',
        'due_date',
        'status',
        'g1_total_sales',
        'g2_export_sales',
        'g3_gst_free_sales',
        'g10_capital_purchases',
        'g11_non_capital_purchases',
        'gst_1a',
        'gst_1b',
        'net_amount',
        'prepared_at',
        'lodged_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'due_date' => 'date',
            'g1_total_sales' => 'decimal:2',
            'g2_export_sales' => 'decimal:2',
            'g3_gst_free_sales' => 'decimal:2',
            'g10_capital_purchases' => 'decimal:2',
            'g11_non_capital_purchases' => 'decimal:2',
            'gst_1a' => 'decimal:2',
            'gst_1b' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'prepared_at' => 'datetime',
            'lodged_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public static function lodgedForBusinessDate(int $businessId, mixed $date): ?self
    {
        if (blank($date)) {
            return null;
        }

        $date = Carbon::parse($date)->toDateString();

        return static::query()
            ->where('book_business_id', $businessId)
            ->where('period_type', 'quarterly')
            ->where('status', 'lodged')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BookBusiness::class, 'book_business_id');
    }

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(BookFinancialYear::class, 'book_financial_year_id');
    }
}
