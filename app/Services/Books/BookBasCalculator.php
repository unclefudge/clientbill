<?php

namespace App\Services\Books;

use App\Models\BookBusiness;
use App\Models\BookTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BookBasCalculator
{
    public const LABELS = [
        'g1_total_sales' => [
            'code' => 'G1',
            'title' => 'Total sales',
            'description' => 'Business sales for the selected period.',
        ],
        'g2_export_sales' => [
            'code' => 'G2',
            'title' => 'Export sales',
            'description' => 'Sales marked as export sales.',
        ],
        'g3_gst_free_sales' => [
            'code' => 'G3',
            'title' => 'Other GST-free sales',
            'description' => 'Sales marked as GST-free, excluding exports.',
        ],
        'g10_capital_purchases' => [
            'code' => 'G10',
            'title' => 'Capital purchases',
            'description' => 'Business-use amount of purchases marked Capital.',
        ],
        'g11_non_capital_purchases' => [
            'code' => 'G11',
            'title' => 'Non-capital purchases',
            'description' => 'Business-use amount of purchases not marked Capital.',
        ],
        'gst_1a' => [
            'code' => '1A',
            'title' => 'GST on sales',
            'description' => 'GST recorded on business income.',
        ],
        'gst_1b' => [
            'code' => '1B',
            'title' => 'GST on purchases',
            'description' => 'GST credits recorded on business purchases.',
        ],
    ];

    public function transactions(
        BookBusiness $business,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
    ): Collection {
        $start = $startDate instanceof CarbonInterface ? $startDate->toDateString() : (string) $startDate;
        $end = $endDate instanceof CarbonInterface ? $endDate->toDateString() : (string) $endDate;

        return BookTransaction::query()
            ->with('category')
            ->where('book_business_id', $business->id)
            ->whereBetween('transaction_date', [$start, $end])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
    }

    public function calculate(
        BookBusiness $business,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
    ): array {
        return $this->calculateTransactions(
            $this->transactions($business, $startDate, $endDate),
        );
    }

    public function calculateTransactions(Collection $transactions): array
    {
        $values = [
            'g1_total_sales' => 0.0,
            'g2_export_sales' => 0.0,
            'g3_gst_free_sales' => 0.0,
            'g10_capital_purchases' => 0.0,
            'g11_non_capital_purchases' => 0.0,
            'gst_1a' => 0.0,
            'gst_1b' => 0.0,
            'net_amount' => 0.0,
        ];

        $counts = array_fill_keys(array_keys(self::LABELS), 0);
        $includedCount = 0;
        $excludedCount = 0;
        $uncategorisedCount = 0;

        foreach ($transactions as $transaction) {
            if ($this->isExcluded($transaction)) {
                $excludedCount++;

                continue;
            }

            $includedCount++;

            if (! $transaction->book_category_id) {
                $uncategorisedCount++;
            }

            $amount = (float) $transaction->amount;
            $businessAmount = abs((float) ($transaction->business_amount ?? $transaction->amount));
            $gstAmount = max(0, (float) $transaction->gst_amount);

            if ($amount > 0) {
                $values['g1_total_sales'] += $businessAmount;
                $counts['g1_total_sales']++;

                $saleType = $this->effectiveSaleType($transaction);

                if ($saleType === 'export') {
                    $values['g2_export_sales'] += $businessAmount;
                    $counts['g2_export_sales']++;
                } elseif ($saleType === 'gst_free') {
                    $values['g3_gst_free_sales'] += $businessAmount;
                    $counts['g3_gst_free_sales']++;
                }

                if ($gstAmount > 0) {
                    $values['gst_1a'] += $gstAmount;
                    $counts['gst_1a']++;
                }

                continue;
            }

            if ($amount < 0) {
                if ($transaction->purchase_type === 'capital') {
                    $values['g10_capital_purchases'] += $businessAmount;
                    $counts['g10_capital_purchases']++;
                } else {
                    $values['g11_non_capital_purchases'] += $businessAmount;
                    $counts['g11_non_capital_purchases']++;
                }

                if ($gstAmount > 0) {
                    $values['gst_1b'] += $gstAmount;
                    $counts['gst_1b']++;
                }
            }
        }

        foreach ($values as $key => $value) {
            if ($key !== 'net_amount') {
                $values[$key] = round($value, 2);
            }
        }

        $values['net_amount'] = round($values['gst_1a'] - $values['gst_1b'], 2);

        $whole = [];

        foreach ($values as $key => $value) {
            $whole[$key] = $this->wholeDollar($value);
        }

        // The BAS net is derived from the whole-dollar 1A and 1B amounts.
        $whole['net_amount'] = $whole['gst_1a'] - $whole['gst_1b'];

        return [
            ...$values,
            'counts' => $counts,
            'transaction_count' => $includedCount,
            'excluded_count' => $excludedCount,
            'uncategorised_count' => $uncategorisedCount,
            'whole' => $whole,
        ];
    }

    public function transactionsForLabel(
        BookBusiness $business,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
        string $label,
    ): Collection {
        abort_unless(array_key_exists($label, self::LABELS), 404);

        return $this->transactions($business, $startDate, $endDate)
            ->filter(fn (BookTransaction $transaction): bool => $this->matchesLabel($transaction, $label))
            ->values();
    }

    public function matchesLabel(BookTransaction $transaction, string $label): bool
    {
        if ($this->isExcluded($transaction)) {
            return false;
        }

        $amount = (float) $transaction->amount;
        $gstAmount = (float) $transaction->gst_amount;

        return match ($label) {
            'g1_total_sales' => $amount > 0,
            'g2_export_sales' => $amount > 0 && $this->effectiveSaleType($transaction) === 'export',
            'g3_gst_free_sales' => $amount > 0 && $this->effectiveSaleType($transaction) === 'gst_free',
            'g10_capital_purchases' => $amount < 0 && $transaction->purchase_type === 'capital',
            'g11_non_capital_purchases' => $amount < 0 && $transaction->purchase_type !== 'capital',
            'gst_1a' => $amount > 0 && $gstAmount > 0,
            'gst_1b' => $amount < 0 && $gstAmount > 0,
            default => false,
        };
    }

    public function contributionForLabel(BookTransaction $transaction, string $label): float
    {
        if (! $this->matchesLabel($transaction, $label)) {
            return 0.0;
        }

        return match ($label) {
            'gst_1a', 'gst_1b' => round((float) $transaction->gst_amount, 2),
            default => round(abs((float) ($transaction->business_amount ?? $transaction->amount)), 2),
        };
    }

    public function definition(string $label): ?array
    {
        return self::LABELS[$label] ?? null;
    }

    protected function effectiveSaleType(BookTransaction $transaction): string
    {
        if (filled($transaction->sale_type)) {
            return (string) $transaction->sale_type;
        }

        return match ($transaction->gst_treatment) {
            'gst_free' => 'gst_free',
            'excluded' => 'excluded',
            default => 'gst',
        };
    }

    protected function isExcluded(BookTransaction $transaction): bool
    {
        return $transaction->gst_treatment === 'excluded'
            || $transaction->sale_type === 'excluded';
    }

    protected function wholeDollar(float $value): int
    {
        return $value >= 0
            ? (int) floor($value)
            : -(int) floor(abs($value));
    }
}
