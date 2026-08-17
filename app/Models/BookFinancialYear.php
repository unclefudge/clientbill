<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookFinancialYear extends Model
{
    protected $fillable = [
        'book_business_id',
        'label',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BookFinancialYear $financialYear): void {
            if (! $financialYear->start_date) {
                return;
            }

            $startYear = Carbon::parse($financialYear->start_date)->year;
            $range = static::rangeForStartYear($startYear);

            // Australian financial years in Books are always 1 July to 30 June.
            // Normalising here keeps this true even outside the Filament form.
            $financialYear->start_date = $range['start_date'];
            $financialYear->end_date = $range['end_date'];
            $financialYear->label = $range['label'];
        });
    }

    public static function rangeForStartYear(int $startYear): array
    {
        $endYear = $startYear + 1;

        return [
            'label' => sprintf('%d–%02d', $startYear, $endYear % 100),
            'start_date' => Carbon::create($startYear, 7, 1)->toDateString(),
            'end_date' => Carbon::create($endYear, 6, 30)->toDateString(),
        ];
    }

    public static function forBusinessDate(int $businessId, mixed $date): ?self
    {
        if (blank($date)) {
            return null;
        }

        $date = Carbon::parse($date)->toDateString();

        return static::query()
            ->where('book_business_id', $businessId)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->first();
    }

    public static function closedForBusinessDate(int $businessId, mixed $date): ?self
    {
        $financialYear = static::forBusinessDate($businessId, $date);

        return $financialYear?->status === 'closed' ? $financialYear : null;
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BookBusiness::class, 'book_business_id');
    }
}
