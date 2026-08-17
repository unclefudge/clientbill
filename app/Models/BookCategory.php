<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookCategory extends Model
{
    public const REPORT_TREATMENTS = [
        'revenue' => 'Revenue',
        'operating_expense' => 'Operating expense',
        'capital_asset' => 'Capital / asset',
        'loan_finance' => 'Loan / finance',
        'distribution_equity' => 'Distribution / equity',
        'tax_ato' => 'Tax / ATO',
        'excluded' => 'Excluded from P&L',
    ];

    protected $fillable = [
        'book_business_id',
        'type',
        'name',
        'report_treatment',
        'report_order',
        'default_gst_treatment',
        'default_business_use_percentage',
        'default_purchase_type',
        'active',
    ];

    protected static function booted(): void
    {
        static::creating(function (BookCategory $category): void {
            if (! $category->report_treatment) {
                $category->report_treatment = static::inferReportTreatment(
                    (string) $category->type,
                    (string) $category->name,
                );
            }

            if ($category->report_order !== null || ! $category->book_business_id) {
                return;
            }

            $category->report_order = ((int) static::query()
                ->where('book_business_id', $category->book_business_id)
                ->max('report_order')) + 10;
        });
    }

    protected function casts(): array
    {
        return [
            'default_business_use_percentage' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public static function inferReportTreatment(string $type, string $name): string
    {
        $name = strtoupper(trim($name));

        if (preg_match('/\bDISTRIBUT(ION|IONS)?\b/', $name)) {
            return 'distribution_equity';
        }

        if (preg_match('/\bLOAN\b/', $name)) {
            return 'loan_finance';
        }

        if (
            preg_match('/\bSHARE\s+(BUY|SALE)\b/', $name)
            || preg_match('/\bINVESTMENT(S)?\b/', $name)
        ) {
            return 'capital_asset';
        }

        if (
            str_contains($name, 'TAX OFFICE')
            || preg_match('/\bATO\b/', $name)
        ) {
            return 'tax_ato';
        }

        return match ($type) {
            'income' => 'revenue',
            'expense' => 'operating_expense',
            default => 'excluded',
        };
    }

    public function effectiveReportTreatment(): string
    {
        return $this->report_treatment
            ?: static::inferReportTreatment((string) $this->type, (string) $this->name);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(BookBusiness::class, 'book_business_id');
    }
}
