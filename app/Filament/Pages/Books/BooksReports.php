<?php

namespace App\Filament\Pages\Books;

use App\Filament\Resources\BookCategories\BookCategoryResource;
use App\Models\BookCategory;
use App\Models\BookFinancialYear;
use App\Models\BookTransaction;
use App\Services\Books\BookProfitLossReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BooksReports extends BookPage
{
    protected string $view = 'filament.pages.books.reports';
    protected static ?string $slug = 'books/reports';

    public string $financialYearFilter = '';
    public string $periodFilter = 'all';

    public bool $showPercentages = false;
    public bool $showComparison = false;

    public ?string $breakdownTreatment = null;
    public ?int $breakdownCategoryId = null;

    public function mount(): void
    {
        $this->mountBookContext();
        $this->setDefaultFinancialYear();
    }

    protected function bookBusinessChanged(): void
    {
        $this->periodFilter = 'all';
        $this->showComparison = false;
        $this->setDefaultFinancialYear();
    }

    public function getFinancialYearsProperty(): Collection
    {
        return BookFinancialYear::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->orderByDesc('start_date')
            ->get();
    }

    public function getFinancialYearOptionsProperty(): array
    {
        return $this->financialYears
            ->mapWithKeys(fn (BookFinancialYear $financialYear): array => [
                (string) $financialYear->id => $financialYear->label,
            ])
            ->all();
    }

    public function getSelectedFinancialYearProperty(): ?BookFinancialYear
    {
        return $this->financialYears
            ->firstWhere('id', (int) $this->financialYearFilter);
    }

    public function getPeriodBoundsProperty(): ?array
    {
        $financialYear = $this->selectedFinancialYear;

        return $financialYear
            ? $this->periodBoundsForFinancialYear($financialYear)
            : null;
    }

    public function getReportProperty(): array
    {
        $bounds = $this->periodBounds;

        if (! $bounds) {
            return $this->emptyReport();
        }

        return app(BookProfitLossReport::class)->calculate(
            $this->getBookBusiness(),
            $bounds['start'],
            $bounds['end'],
        );
    }

    /**
     * Compare the selected year with up to four previous financial years.
     *
     * Previous years always show their COMPLETE matching period.
     * If the selected/current period is still in progress, only that selected
     * period is cut off at today. This makes it easy to see where the current
     * year sits now against completed prior years.
     */
    public function getComparisonProperty(): array
    {
        $selected = $this->selectedFinancialYear;

        if (! $selected) {
            return $this->emptyComparison();
        }

        $selectedBounds = $this->periodBoundsForFinancialYear($selected);
        $today = now('Australia/Hobart')->startOfDay();

        $partial = $selectedBounds['start']->lte($today)
            && $selectedBounds['end']->gt($today);

        $years = $this->financialYears
            ->filter(fn (BookFinancialYear $year): bool =>
                $year->start_date->lte($selected->start_date)
            )
            ->take(5)
            ->reverse()
            ->values();

        $yearData = [];
        $revenueRows = [];
        $expenseRows = [];

        foreach ($years as $year) {
            $bounds = $this->periodBoundsForFinancialYear($year);
            $isSelected = $year->id === $selected->id;
            $isSelectedPartial = $isSelected && $partial;

            // Only the selected/current period is YTD / QTD.
            // Every previous year keeps its full financial year (or full selected quarter).
            if ($isSelectedPartial) {
                $bounds['end'] = $today->copy();
            }

            $report = app(BookProfitLossReport::class)->calculate(
                $this->getBookBusiness(),
                $bounds['start'],
                $bounds['end'],
            );

            $yearKey = (string) $year->id;

            $yearData[] = [
                'id' => $year->id,
                'key' => $yearKey,
                'label' => $year->label,
                'selected' => $isSelected,
                'partial' => $isSelectedPartial,
                'start' => $bounds['start'],
                'end' => $bounds['end'],
                'period_note' => $isSelectedPartial
                    ? 'to ' . $bounds['end']->format('j M')
                    : ($this->periodFilter === 'all' ? 'full year' : 'full quarter'),
                'revenue_total' => $report['revenue']['total'],
                'expense_total' => $report['operating_expenses']['total'],
                'net_profit' => $report['net_profit'],
            ];

            $this->mergeComparisonRows(
                $revenueRows,
                $report['revenue']['groups'],
                $yearKey,
            );

            $this->mergeComparisonRows(
                $expenseRows,
                $report['operating_expenses']['groups'],
                $yearKey,
            );
        }

        $categoryIds = collect(array_keys($revenueRows))
            ->merge(array_keys($expenseRows))
            ->map(fn ($id): int => (int) $id)
            ->filter()
            ->unique()
            ->values();

        $categoryActive = BookCategory::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->whereIn('id', $categoryIds)
            ->pluck('active', 'id');

        $revenueRows = $this->decorateComparisonRows(
            $this->sortedComparisonRows($revenueRows),
            $categoryActive,
        );

        $expenseRows = $this->decorateComparisonRows(
            $this->sortedComparisonRows($expenseRows),
            $categoryActive,
        );

        return [
            'years' => $yearData,
            'revenue' => $revenueRows,
            'expenses' => $expenseRows,
            'partial' => $partial,
        ];
    }

    public function getComparisonDescriptionProperty(): string
    {
        $comparison = $this->comparison;

        if ($comparison['partial']) {
            $current = collect($comparison['years'])->firstWhere('selected', true);

            return 'Previous years show their full '
                . ($this->periodFilter === 'all' ? 'financial year' : 'quarter')
                . '. The selected current period is shown '
                . ($current['period_note'] ?? 'to today')
                . ', so you can see where it currently sits against completed prior periods.';
        }

        return 'Each comparison year shows the complete '
            . ($this->periodFilter === 'all' ? 'financial year' : 'selected quarter')
            . '.';
    }

    public function getCategoriesUrlProperty(): string
    {
        return BookCategoryResource::getUrl('index');
    }

    public function openBreakdown(string $treatment, int $categoryId): void
    {
        abort_unless(
            array_key_exists($treatment, \App\Models\BookCategory::REPORT_TREATMENTS),
            404,
        );

        $this->breakdownTreatment = $treatment;
        $this->breakdownCategoryId = $categoryId;

        $this->dispatch('open-modal', id: 'bookReportBreakdown');
    }

    public function getBreakdownTransactionsProperty(): Collection
    {
        $bounds = $this->periodBounds;

        if (! $bounds || ! $this->breakdownTreatment || $this->breakdownCategoryId === null) {
            return collect();
        }

        return app(BookProfitLossReport::class)->breakdownTransactions(
            $this->getBookBusiness(),
            $bounds['start'],
            $bounds['end'],
            $this->breakdownTreatment,
            $this->breakdownCategoryId,
        );
    }

    public function getBreakdownTitleProperty(): string
    {
        $first = $this->breakdownTransactions->first();

        return $first?->category?->name ?? 'Uncategorised';
    }

    public function getBreakdownTreatmentLabelProperty(): string
    {
        return \App\Models\BookCategory::REPORT_TREATMENTS[$this->breakdownTreatment]
            ?? 'Report detail';
    }

    public function getBreakdownTotalProperty(): float
    {
        return round(
            $this->breakdownTransactions
                ->sum(fn (BookTransaction $transaction): float =>
                    app(BookProfitLossReport::class)->contribution($transaction)
                ),
            2,
        );
    }

    public function breakdownContribution(BookTransaction $transaction): float
    {
        return app(BookProfitLossReport::class)->contribution($transaction);
    }

    public function exportCsv(): StreamedResponse
    {
        $business = $this->getBookBusiness();
        $bounds = $this->periodBounds;
        $report = $this->report;
        $comparison = $this->comparison;

        $filename = sprintf(
            'profit-loss-%s-%s.csv',
            Str::slug($business->name ?: 'books'),
            Str::slug($bounds['label'] ?? 'report'),
        );

        return response()->streamDownload(
            function () use ($business, $bounds, $report, $comparison): void {
                $out = fopen('php://output', 'w');

                // Excel-friendly UTF-8 BOM.
                fwrite($out, "\xEF\xBB\xBF");

                fputcsv($out, ['Business', $business->name]);
                fputcsv($out, ['Report', 'Profit & Loss']);
                fputcsv($out, ['Period', $bounds['label'] ?? '']);
                fputcsv($out, ['Generated', now('Australia/Hobart')->format('j M Y g:i a')]);
                fputcsv($out, []);

                fputcsv($out, ['Section', 'Category', 'Amount', 'Share %', 'Transactions']);

                foreach ($report['revenue']['groups'] as $group) {
                    $share = $report['revenue']['total'] != 0
                        ? abs($group['amount']) / abs($report['revenue']['total']) * 100
                        : 0;

                    fputcsv($out, [
                        'Income',
                        $group['name'],
                        number_format((float) $group['amount'], 2, '.', ''),
                        number_format($share, 2, '.', ''),
                        $group['count'],
                    ]);
                }

                fputcsv($out, [
                    'Income',
                    'Total income',
                    number_format((float) $report['revenue']['total'], 2, '.', ''),
                    '100.00',
                    '',
                ]);

                foreach ($report['operating_expenses']['groups'] as $group) {
                    $share = $report['operating_expenses']['total'] != 0
                        ? abs($group['amount']) / abs($report['operating_expenses']['total']) * 100
                        : 0;

                    fputcsv($out, [
                        'Operating expenses',
                        $group['name'],
                        number_format((float) $group['amount'], 2, '.', ''),
                        number_format($share, 2, '.', ''),
                        $group['count'],
                    ]);
                }

                fputcsv($out, [
                    'Operating expenses',
                    'Total expenses',
                    number_format((float) $report['operating_expenses']['total'], 2, '.', ''),
                    '100.00',
                    '',
                ]);

                fputcsv($out, [
                    'Profit & Loss',
                    'Net profit',
                    number_format((float) $report['net_profit'], 2, '.', ''),
                    '',
                    '',
                ]);

                if ($report['outside_pnl'] !== []) {
                    fputcsv($out, []);

                    foreach ($report['outside_pnl'] as $section) {
                        foreach ($section['groups'] as $group) {
                            fputcsv($out, [
                                'Not included - ' . $section['label'],
                                $group['name'],
                                number_format((float) $group['amount'], 2, '.', ''),
                                '',
                                $group['count'],
                            ]);
                        }
                    }
                }

                if ($comparison['years'] !== []) {
                    fputcsv($out, []);
                    fputcsv($out, ['YEAR COMPARISON']);

                    $yearLabels = array_map(
                        fn (array $year): string => $year['label'],
                        $comparison['years'],
                    );

                    fputcsv($out, array_merge(['Income category'], $yearLabels));

                    foreach ($comparison['revenue'] as $row) {
                        $values = array_map(
                            fn (array $year): string =>
                                isset($row['values'][$year['key']])
                                    ? number_format((float) $row['values'][$year['key']], 2, '.', '')
                                    : '',
                            $comparison['years'],
                        );

                        fputcsv($out, array_merge([$row['name']], $values));
                    }

                    fputcsv($out, array_merge(
                        ['Total income'],
                        array_map(
                            fn (array $year): string =>
                                number_format((float) $year['revenue_total'], 2, '.', ''),
                            $comparison['years'],
                        ),
                    ));

                    fputcsv($out, []);
                    fputcsv($out, array_merge(['Expense category'], $yearLabels));

                    foreach ($comparison['expenses'] as $row) {
                        $values = array_map(
                            fn (array $year): string =>
                                isset($row['values'][$year['key']])
                                    ? number_format((float) $row['values'][$year['key']], 2, '.', '')
                                    : '',
                            $comparison['years'],
                        );

                        fputcsv($out, array_merge([$row['name']], $values));
                    }

                    fputcsv($out, array_merge(
                        ['Total expenses'],
                        array_map(
                            fn (array $year): string =>
                                number_format((float) $year['expense_total'], 2, '.', ''),
                            $comparison['years'],
                        ),
                    ));

                    fputcsv($out, array_merge(
                        ['Net profit'],
                        array_map(
                            fn (array $year): string =>
                                number_format((float) $year['net_profit'], 2, '.', ''),
                            $comparison['years'],
                        ),
                    ));
                }

                fclose($out);
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    protected function setDefaultFinancialYear(): void
    {
        $today = now('Australia/Hobart');

        $financialYear = $this->financialYears->first(
            fn (BookFinancialYear $year): bool =>
                $today->toDateString() >= $year->start_date->toDateString()
                && $today->toDateString() <= $year->end_date->toDateString()
        ) ?? $this->financialYears->first();

        $this->financialYearFilter = $financialYear
            ? (string) $financialYear->id
            : '';
    }

    protected function periodBoundsForFinancialYear(BookFinancialYear $financialYear): array
    {
        if ($this->periodFilter === 'all') {
            return [
                'start' => $financialYear->start_date->copy(),
                'end' => $financialYear->end_date->copy(),
                'label' => $financialYear->label . ' · Full financial year',
            ];
        }

        $quarterIndex = match ($this->periodFilter) {
            'q1' => 0,
            'q2' => 1,
            'q3' => 2,
            'q4' => 3,
            default => 0,
        };

        $start = $financialYear->start_date->copy()->addMonths($quarterIndex * 3);
        $end = $start->copy()->addMonths(3)->subDay();

        if ($end->gt($financialYear->end_date)) {
            $end = $financialYear->end_date->copy();
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => strtoupper($this->periodFilter)
                . ' · '
                . $start->format('j M Y')
                . ' – '
                . $end->format('j M Y'),
        ];
    }

    protected function mergeComparisonRows(array &$rows, array $groups, string $yearKey): void
    {
        foreach ($groups as $group) {
            $key = (string) $group['category_id'];

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'category_id' => $group['category_id'],
                    'name' => $group['name'],
                    'report_order' => $group['report_order'] ?? 999999,
                    'values' => [],
                ];
            }

            $rows[$key]['values'][$yearKey] = (float) $group['amount'];
        }
    }

    protected function sortedComparisonRows(array $rows): array
    {
        $rows = array_values($rows);

        usort($rows, fn (array $a, array $b): int =>
            [$a['report_order'], $a['name']]
            <=> [$b['report_order'], $b['name']]
        );

        return $rows;
    }

    protected function decorateComparisonRows(array $rows, $categoryActive): array
    {
        foreach ($rows as &$row) {
            $categoryId = (int) ($row['category_id'] ?? 0);

            $row['active'] = $categoryActive->has($categoryId)
                ? (bool) $categoryActive->get($categoryId)
                : null;
        }
        unset($row);

        return $rows;
    }

    protected function emptyComparison(): array
    {
        return [
            'years' => [],
            'revenue' => [],
            'expenses' => [],
            'partial' => false,
        ];
    }

    protected function emptyReport(): array
    {
        return [
            'revenue' => ['total' => 0.0, 'groups' => []],
            'operating_expenses' => ['total' => 0.0, 'groups' => []],
            'net_profit' => 0.0,
            'outside_pnl' => [],
            'transaction_count' => 0,
            'pnl_transaction_count' => 0,
            'outside_transaction_count' => 0,
        ];
    }
}
