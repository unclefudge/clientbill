<?php

namespace App\Services\Books;

use App\Models\BookBusiness;
use App\Models\BookCategory;
use App\Models\BookTransaction;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class BookProfitLossReport
{
    public const OUTSIDE_PNL = [
        'capital_asset' => [
            'label' => 'Capital / asset',
            'description' => 'Capital purchases and asset/investment movements are kept outside operating profit.',
        ],
        'loan_finance' => [
            'label' => 'Loan / finance',
            'description' => 'Loan principal movements are not ordinary business income or operating expenses.',
        ],
        'distribution_equity' => [
            'label' => 'Distribution / equity',
            'description' => 'Owner/trust distributions and equity movements are outside operating profit.',
        ],
        'tax_ato' => [
            'label' => 'Tax / ATO',
            'description' => 'ATO and tax payments are shown separately rather than as operating expenses.',
        ],
        'excluded' => [
            'label' => 'Excluded from P&L',
            'description' => 'Categories deliberately excluded from the Profit & Loss report.',
        ],
    ];

    public function transactions(
        BookBusiness $business,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
    ): Collection {
        $start = $startDate instanceof CarbonInterface
            ? $startDate->toDateString()
            : (string) $startDate;

        $end = $endDate instanceof CarbonInterface
            ? $endDate->toDateString()
            : (string) $endDate;

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
        $revenueGroups = [];
        $expenseGroups = [];
        $outsideGroups = [];

        $revenueTotal = 0.0;
        $expenseTotal = 0.0;
        $pnlTransactionCount = 0;

        foreach ($transactions as $transaction) {
            $treatment = $this->effectiveTreatment($transaction);
            $signedNet = $this->signedNetAmount($transaction);

            $categoryId = (int) ($transaction->book_category_id ?? 0);
            $categoryName = $transaction->category?->name ?? 'Uncategorised';
            $reportOrder = (int) ($transaction->category?->report_order ?? 999999);

            if ($treatment === 'revenue') {
                $contribution = $signedNet;
                $revenueTotal += $contribution;
                $pnlTransactionCount++;

                $this->addGroup(
                    $revenueGroups,
                    $categoryId,
                    $categoryName,
                    $reportOrder,
                    $contribution,
                );

                continue;
            }

            if ($treatment === 'operating_expense') {
                // Expense rows are stored negative. A positive refund/credit therefore
                // reduces operating expenses rather than increasing them.
                $contribution = -$signedNet;
                $expenseTotal += $contribution;
                $pnlTransactionCount++;

                $this->addGroup(
                    $expenseGroups,
                    $categoryId,
                    $categoryName,
                    $reportOrder,
                    $contribution,
                );

                continue;
            }

            $key = $treatment . '|' . $categoryId;

            if (! isset($outsideGroups[$treatment])) {
                $outsideGroups[$treatment] = [
                    'treatment' => $treatment,
                    'label' => self::OUTSIDE_PNL[$treatment]['label']
                        ?? BookCategory::REPORT_TREATMENTS[$treatment]
                        ?? 'Excluded from P&L',
                    'description' => self::OUTSIDE_PNL[$treatment]['description']
                        ?? 'This movement is not included in operating profit.',
                    'groups' => [],
                    'net_movement' => 0.0,
                    'transaction_count' => 0,
                ];
            }

            if (! isset($outsideGroups[$treatment]['groups'][$key])) {
                $outsideGroups[$treatment]['groups'][$key] = [
                    'category_id' => $categoryId,
                    'name' => $categoryName,
                    'report_order' => $reportOrder,
                    'amount' => 0.0,
                    'count' => 0,
                ];
            }

            $outsideGroups[$treatment]['groups'][$key]['amount'] += $signedNet;
            $outsideGroups[$treatment]['groups'][$key]['count']++;
            $outsideGroups[$treatment]['net_movement'] += $signedNet;
            $outsideGroups[$treatment]['transaction_count']++;
        }

        $revenueGroups = $this->sortedGroups($revenueGroups);
        $expenseGroups = $this->sortedGroups($expenseGroups);

        foreach ($outsideGroups as &$section) {
            $section['groups'] = $this->sortedGroups($section['groups']);
            $section['net_movement'] = round($section['net_movement'], 2);
        }
        unset($section);

        $outsideGroups = collect(self::OUTSIDE_PNL)
            ->keys()
            ->mapWithKeys(fn (string $key): array =>
                isset($outsideGroups[$key])
                    ? [$key => $outsideGroups[$key]]
                    : []
            )
            ->all();

        return [
            'revenue' => [
                'total' => round($revenueTotal, 2),
                'groups' => $revenueGroups,
            ],
            'operating_expenses' => [
                'total' => round($expenseTotal, 2),
                'groups' => $expenseGroups,
            ],
            'net_profit' => round($revenueTotal - $expenseTotal, 2),
            'outside_pnl' => $outsideGroups,
            'transaction_count' => $transactions->count(),
            'pnl_transaction_count' => $pnlTransactionCount,
            'outside_transaction_count' => collect($outsideGroups)
                ->sum('transaction_count'),
        ];
    }

    public function breakdownTransactions(
        BookBusiness $business,
        CarbonInterface|string $startDate,
        CarbonInterface|string $endDate,
        string $treatment,
        int $categoryId,
    ): Collection {
        return $this->transactions($business, $startDate, $endDate)
            ->filter(fn (BookTransaction $transaction): bool =>
                $this->effectiveTreatment($transaction) === $treatment
                && (int) ($transaction->book_category_id ?? 0) === $categoryId
            )
            ->values();
    }

    public function effectiveTreatment(BookTransaction $transaction): string
    {
        $category = $transaction->category;

        $treatment = $category
            ? $category->effectiveReportTreatment()
            : 'excluded';

        // A category such as IT EQUIPMENT can legitimately contain both ordinary
        // consumables and capital purchases. Capital is therefore a transaction-level
        // override only when the category is otherwise an operating expense.
        if (
            $treatment === 'operating_expense'
            && (float) $transaction->amount < 0
            && $transaction->purchase_type === 'capital'
        ) {
            return 'capital_asset';
        }

        return $treatment;
    }

    public function signedNetAmount(BookTransaction $transaction): float
    {
        $grossBusiness = abs((float) (
            $transaction->business_amount
            ?? $transaction->amount
            ?? 0
        ));

        $gst = max(0, (float) ($transaction->gst_amount ?? 0));
        $net = max(0, $grossBusiness - $gst);

        return (float) $transaction->amount < 0
            ? -$net
            : $net;
    }

    public function contribution(BookTransaction $transaction): float
    {
        $signedNet = $this->signedNetAmount($transaction);

        return $this->effectiveTreatment($transaction) === 'operating_expense'
            ? -$signedNet
            : $signedNet;
    }

    protected function addGroup(
        array &$groups,
        int $categoryId,
        string $categoryName,
        int $reportOrder,
        float $amount,
    ): void {
        $key = (string) $categoryId;

        if (! isset($groups[$key])) {
            $groups[$key] = [
                'category_id' => $categoryId,
                'name' => $categoryName,
                'report_order' => $reportOrder,
                'amount' => 0.0,
                'count' => 0,
            ];
        }

        $groups[$key]['amount'] += $amount;
        $groups[$key]['count']++;
    }

    protected function sortedGroups(array $groups): array
    {
        $groups = array_values($groups);

        usort($groups, fn (array $a, array $b): int =>
            [$a['report_order'], $a['name']]
            <=> [$b['report_order'], $b['name']]
        );

        foreach ($groups as &$group) {
            $group['amount'] = round($group['amount'], 2);
        }
        unset($group);

        return $groups;
    }
}
