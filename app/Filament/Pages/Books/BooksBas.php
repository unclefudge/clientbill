<?php

namespace App\Filament\Pages\Books;

use App\Models\BookBasPeriod;
use App\Models\BookFinancialYear;
use App\Models\BookTransaction;
use App\Services\Books\BookBasCalculator;
use App\Services\Books\BookBasDueDateService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

class BooksBas extends BookPage
{
    protected string $view = 'filament.pages.books.bas';
    protected static ?string $slug = 'books/bas';

    public string $financialYearFilter = '';
    public string $periodFilter = 'q1';
    public ?string $breakdownLabel = null;
    public bool $showBasHistory = false;

    public function mount(): void
    {
        $this->mountBookContext();
        $this->setDefaultPeriod();
    }

    protected function bookBusinessChanged(): void
    {
        $this->showBasHistory = false;
        $this->setDefaultPeriod();
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
            ->mapWithKeys(fn (BookFinancialYear $financialYear) => [
                (string) $financialYear->id => $financialYear->label,
            ])
            ->all();
    }

    public function getSelectedFinancialYearProperty(): ?BookFinancialYear
    {
        return $this->financialYears->firstWhere('id', (int) $this->financialYearFilter);
    }

    public function getPeriodBoundsProperty(): ?array
    {
        $financialYear = $this->selectedFinancialYear;

        if (! $financialYear) {
            return null;
        }

        $quarterIndex = match ($this->periodFilter) {
            'q1' => 0,
            'q2' => 1,
            'q3' => 2,
            'q4' => 3,
            default => null,
        };

        if ($quarterIndex === null) {
            return [
                'start' => $financialYear->start_date->copy(),
                'end' => $financialYear->end_date->copy(),
                'period_type' => 'annual',
                'label' => $financialYear->label . ' Annual summary',
            ];
        }

        $start = $financialYear->start_date->copy()->addMonths($quarterIndex * 3);
        $end = $start->copy()->addMonths(3)->subDay();

        if ($end->gt($financialYear->end_date)) {
            $end = $financialYear->end_date->copy();
        }

        return [
            'start' => $start,
            'end' => $end,
            'period_type' => 'quarterly',
            'label' => strtoupper($this->periodFilter) . ' · ' . $start->format('j M Y') . ' – ' . $end->format('j M Y'),
        ];
    }

    public function getBasProperty(): array
    {
        $bounds = $this->periodBounds;

        if (! $bounds) {
            return $this->emptyBas();
        }

        return app(BookBasCalculator::class)->calculate(
            $this->getBookBusiness(),
            $bounds['start'],
            $bounds['end'],
        );
    }

    public function getSavedBasPeriodProperty(): ?BookBasPeriod
    {
        $bounds = $this->periodBounds;

        if (! $bounds) {
            return null;
        }

        return BookBasPeriod::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->whereDate('start_date', $bounds['start']->toDateString())
            ->whereDate('end_date', $bounds['end']->toDateString())
            ->where('period_type', $bounds['period_type'])
            ->first();
    }

    public function getQuarterStatusesProperty(): array
    {
        $financialYear = $this->selectedFinancialYear;

        if (! $financialYear) {
            return [];
        }

        $savedPeriods = BookBasPeriod::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->where('book_financial_year_id', $financialYear->id)
            ->where('period_type', 'quarterly')
            ->get()
            ->keyBy(fn (BookBasPeriod $period): string => $period->start_date->toDateString());

        $statuses = [];

        foreach ($this->quarterLabels() as $index => $label) {
            $key = 'q' . ($index + 1);
            $start = $financialYear->start_date->copy()->addMonths($index * 3);
            $saved = $savedPeriods->get($start->toDateString());

            $statuses[$key] = $this->buildQuarterStatus(
                $financialYear,
                $key,
                $label,
                $index,
                $saved,
            );
        }

        return $statuses;
    }

    public function getSelectedQuarterStatusProperty(): ?array
    {
        if (! in_array($this->periodFilter, ['q1', 'q2', 'q3', 'q4'], true)) {
            return null;
        }

        return $this->quarterStatuses[$this->periodFilter] ?? null;
    }

    public function getBasHistoryProperty(): array
    {
        if (! $this->showBasHistory) {
            return [];
        }

        $financialYears = $this->financialYears;

        if ($financialYears->isEmpty()) {
            return [];
        }

        $financialYearIds = $financialYears->pluck('id');

        $savedPeriods = BookBasPeriod::query()
            ->where('book_business_id', $this->getBookBusiness()->id)
            ->whereIn('book_financial_year_id', $financialYearIds)
            ->where('period_type', 'quarterly')
            ->get()
            ->groupBy('book_financial_year_id');

        $today = now('Australia/Hobart')->startOfDay();
        $oldestStart = $financialYears->min('start_date');

        $allTransactions = $oldestStart
            ? app(BookBasCalculator::class)->transactions(
                $this->getBookBusiness(),
                $oldestStart,
                $today,
            )
            : collect();

        $calculator = app(BookBasCalculator::class);
        $rows = [];

        foreach ($financialYears as $financialYear) {
            $yearSavedPeriods = $savedPeriods
                ->get($financialYear->id, collect())
                ->keyBy(fn (BookBasPeriod $period): string => $period->start_date->toDateString());

            foreach ($this->quarterLabels() as $index => $label) {
                $key = 'q' . ($index + 1);
                $start = $financialYear->start_date->copy()->addMonths($index * 3);
                $end = $start->copy()->addMonths(3)->subDay();

                if ($end->gt($financialYear->end_date)) {
                    $end = $financialYear->end_date->copy();
                }

                // BAS History is history/current activity, not a future-quarter planner.
                if ($start->gt($today)) {
                    continue;
                }

                $saved = $yearSavedPeriods->get($start->toDateString());

                $status = $this->buildQuarterStatus(
                    $financialYear,
                    $key,
                    $label,
                    $index,
                    $saved,
                );

                $usesSnapshot = $saved
                    && in_array($saved->status, ['lodged', 'historical'], true);

                if ($usesSnapshot) {
                    $gst1a = (float) $saved->gst_1a;
                    $gst1b = (float) $saved->gst_1b;
                    $figureSource = 'Snapshot';
                } else {
                    $quarterTransactions = $allTransactions
                        ->filter(fn (BookTransaction $transaction): bool =>
                            $transaction->transaction_date->betweenIncluded($start, $end)
                        )
                        ->values();

                    $calculated = $calculator->calculateTransactions($quarterTransactions);
                    $gst1a = (float) $calculated['gst_1a'];
                    $gst1b = (float) $calculated['gst_1b'];
                    $figureSource = $financialYear->status === 'closed'
                        ? 'Locked Books'
                        : 'Live Books';
                }

                $whole1a = $this->wholeDollar($gst1a);
                $whole1b = $this->wholeDollar($gst1b);
                $wholeNet = $whole1a - $whole1b;

                $rows[] = [
                    ...$status,
                    'financial_year_id' => $financialYear->id,
                    'financial_year_label' => $financialYear->label,
                    'financial_year_closed' => $financialYear->status === 'closed',
                    'selected' => (int) $this->financialYearFilter === (int) $financialYear->id
                        && $this->periodFilter === $key,
                    'uses_snapshot' => $usesSnapshot,
                    'figure_source' => $figureSource,
                    'gst_1a' => $gst1a,
                    'gst_1b' => $gst1b,
                    'whole_1a' => $whole1a,
                    'whole_1b' => $whole1b,
                    'whole_net' => $wholeNet,
                    'date_label' => $this->historyDateLabel($status, $saved),
                ];
            }
        }

        return $rows;
    }

    public function selectBasHistoryPeriod(int $financialYearId, string $period): void
    {
        if (! in_array($period, ['q1', 'q2', 'q3', 'q4'], true)) {
            return;
        }

        $financialYear = $this->financialYears->firstWhere('id', $financialYearId);

        if (! $financialYear) {
            return;
        }

        $this->financialYearFilter = (string) $financialYear->id;
        $this->periodFilter = $period;
        $this->breakdownLabel = null;
    }

    public function getCanLodgeSelectedPeriodProperty(): bool
    {
        $financialYear = $this->selectedFinancialYear;
        $bounds = $this->periodBounds;
        $status = $this->selectedQuarterStatus;

        if (
            ! $financialYear
            || ! $bounds
            || ! $status
            || $bounds['period_type'] !== 'quarterly'
            || $financialYear->status === 'closed'
            || $status['status'] === 'lodged'
        ) {
            return false;
        }

        return now('Australia/Hobart')->startOfDay()->gt(
            $bounds['end']->copy()->startOfDay(),
        );
    }

    public function getLodgeButtonLabelProperty(): string
    {
        return ($this->selectedQuarterStatus['status'] ?? null) === 'reopened'
            ? 'Re-lodge BAS'
            : 'Mark BAS as lodged';
    }

    public function openLodgeConfirmation(): void
    {
        if (! $this->canLodgeSelectedPeriod) {
            return;
        }

        if ($this->bas['uncategorised_count'] > 0) {
            Notification::make()
                ->warning()
                ->title('Uncategorised transactions')
                ->body('Categorise all transactions in this BAS period before lodging it.')
                ->send();

            return;
        }

        $this->dispatch('open-modal', id: 'bookBasLodgeConfirmation');
    }

    public function lodgeBas(): void
    {
        if (! $this->canLodgeSelectedPeriod) {
            Notification::make()
                ->warning()
                ->title('This BAS period cannot be lodged yet')
                ->send();

            return;
        }

        if ($this->bas['uncategorised_count'] > 0) {
            Notification::make()
                ->warning()
                ->title('Uncategorised transactions')
                ->body('Categorise all transactions in this BAS period before lodging it.')
                ->send();

            return;
        }

        $financialYear = $this->selectedFinancialYear;
        $bounds = $this->periodBounds;
        $bas = $this->bas;
        $dueDate = app(BookBasDueDateService::class)->dueDate(
            $bounds['end'],
            $this->savedBasPeriod?->due_date,
        );

        $period = BookBasPeriod::query()->updateOrCreate(
            [
                'book_business_id' => $this->getBookBusiness()->id,
                'start_date' => $bounds['start']->toDateString(),
                'end_date' => $bounds['end']->toDateString(),
                'period_type' => 'quarterly',
            ],
            [
                'book_financial_year_id' => $financialYear->id,
                'period_label' => $this->quarterPeriodLabel($bounds['start'], $bounds['end']),
                'due_date' => $dueDate->toDateString(),
                'status' => 'lodged',
                'g1_total_sales' => $bas['g1_total_sales'],
                'g2_export_sales' => $bas['g2_export_sales'],
                'g3_gst_free_sales' => $bas['g3_gst_free_sales'],
                'g10_capital_purchases' => $bas['g10_capital_purchases'],
                'g11_non_capital_purchases' => $bas['g11_non_capital_purchases'],
                'gst_1a' => $bas['gst_1a'],
                'gst_1b' => $bas['gst_1b'],
                'net_amount' => $bas['net_amount'],
                'prepared_at' => now('Australia/Hobart'),
                'lodged_at' => now('Australia/Hobart'),
                'paid_at' => null,
            ],
        );

        $this->dispatch('close-modal', id: 'bookBasLodgeConfirmation');

        Notification::make()
            ->success()
            ->title($period->period_label . ' lodged')
            ->body('The BAS snapshot has been saved and transactions in this period are now locked.')
            ->send();
    }

    public function openReopenConfirmation(): void
    {
        if ($this->savedBasPeriod?->status !== 'lodged') {
            return;
        }

        $this->dispatch('open-modal', id: 'bookBasReopenConfirmation');
    }

    public function reopenBas(): void
    {
        $period = $this->savedBasPeriod;

        if (! $period || $period->status !== 'lodged') {
            return;
        }

        if ($this->selectedFinancialYear?->status === 'closed') {
            Notification::make()
                ->warning()
                ->title($this->selectedFinancialYear->label . ' is closed')
                ->body('Reopen the financial year before reopening this BAS period.')
                ->send();

            return;
        }

        $period->update([
            'status' => 'reopened',
            'prepared_at' => now('Australia/Hobart'),
            'paid_at' => null,
        ]);

        $this->dispatch('close-modal', id: 'bookBasReopenConfirmation');

        Notification::make()
            ->warning()
            ->title($period->period_label . ' reopened')
            ->body('Transactions in this BAS period are editable again. Re-lodge the BAS after making corrections.')
            ->send();
    }

    public function getValidationProperty(): ?array
    {
        $saved = $this->savedBasPeriod;

        if (! $saved) {
            return null;
        }

        $fields = [
            'g1_total_sales' => 'G1',
            'g2_export_sales' => 'G2',
            'g3_gst_free_sales' => 'G3',
            'g10_capital_purchases' => 'G10',
            'g11_non_capital_purchases' => 'G11',
            'gst_1a' => '1A',
            'gst_1b' => '1B',
            'net_amount' => 'Net GST',
        ];

        $rows = [];
        $allMatch = true;

        foreach ($fields as $field => $label) {
            $live = round((float) ($this->bas[$field] ?? 0), 2);
            $snapshot = round((float) $saved->{$field}, 2);
            $difference = round($live - $snapshot, 2);
            $matches = abs($difference) < 0.005;

            $allMatch = $allMatch && $matches;

            $rows[] = [
                'field' => $field,
                'label' => $label,
                'saved' => $snapshot,
                'live' => $live,
                'difference' => $difference,
                'matches' => $matches,
            ];
        }

        return [
            'all_match' => $allMatch,
            'rows' => $rows,
        ];
    }

    public function getGstRegisteredProperty(): bool
    {
        return (bool) $this->getBookBusiness()->gst_registered;
    }

    public function getPrintBasProperty(): array
    {
        $live = $this->bas;
        $saved = $this->savedBasPeriod;

        // A lodged or historical BAS is an accounting record. Print the saved
        // snapshot rather than silently recalculating it from transactions that
        // may later have changed.
        $useSnapshot = $saved
            && in_array($saved->status, ['lodged', 'historical'], true);

        $fields = [
            'g1_total_sales',
            'g2_export_sales',
            'g3_gst_free_sales',
            'g10_capital_purchases',
            'g11_non_capital_purchases',
            'gst_1a',
            'gst_1b',
            'net_amount',
        ];

        $values = [];

        foreach ($fields as $field) {
            $values[$field] = $useSnapshot
                ? (float) $saved->{$field}
                : (float) ($live[$field] ?? 0);
        }

        $whole = [
            'g1_total_sales' => $this->wholeDollar($values['g1_total_sales']),
            'g2_export_sales' => $this->wholeDollar($values['g2_export_sales']),
            'g3_gst_free_sales' => $this->wholeDollar($values['g3_gst_free_sales']),
            'g10_capital_purchases' => $this->wholeDollar($values['g10_capital_purchases']),
            'g11_non_capital_purchases' => $this->wholeDollar($values['g11_non_capital_purchases']),
            'gst_1a' => $this->wholeDollar($values['gst_1a']),
            'gst_1b' => $this->wholeDollar($values['gst_1b']),
        ];

        // BAS label 9 follows the same whole-dollar rule as the on-screen BAS:
        // truncate 1A and 1B individually, then subtract.
        $whole['net_amount'] = $whole['gst_1a'] - $whole['gst_1b'];

        $status = $saved?->status;

        return [
            ...$values,
            'whole' => $whole,
            'uses_snapshot' => $useSnapshot,
            'source_label' => match (true) {
                $useSnapshot && $status === 'lodged' => 'Lodged BAS snapshot',
                $useSnapshot && $status === 'historical' => 'Historical BAS snapshot',
                $status === 'reopened' => 'Live calculation · BAS reopened',
                default => 'Live BAS calculation',
            },
            'status_label' => match ($status) {
                'lodged' => 'Lodged',
                'reopened' => 'Reopened',
                'historical' => 'Historical',
                default => ucfirst($this->selectedQuarterStatus['status'] ?? 'Open'),
            },
            'lodged_at' => $saved?->lodged_at,
            'due_date' => $saved?->due_date ?? ($this->selectedQuarterStatus['due_date'] ?? null),
        ];
    }

    public function openBreakdown(string $label): void
    {
        abort_unless(app(BookBasCalculator::class)->definition($label), 404);

        $this->breakdownLabel = $label;
        $this->dispatch('open-modal', id: 'bookBasBreakdown');
    }

    public function getBreakdownDefinitionProperty(): ?array
    {
        if (! $this->breakdownLabel) {
            return null;
        }

        return app(BookBasCalculator::class)->definition($this->breakdownLabel);
    }

    public function getBreakdownTransactionsProperty(): Collection
    {
        $bounds = $this->periodBounds;

        if (! $bounds || ! $this->breakdownLabel) {
            return collect();
        }

        return app(BookBasCalculator::class)->transactionsForLabel(
            $this->getBookBusiness(),
            $bounds['start'],
            $bounds['end'],
            $this->breakdownLabel,
        );
    }

    public function getBreakdownTotalProperty(): float
    {
        if (! $this->breakdownLabel) {
            return 0.0;
        }

        return (float) ($this->bas[$this->breakdownLabel] ?? 0);
    }

    public function breakdownContribution(BookTransaction $transaction): float
    {
        if (! $this->breakdownLabel) {
            return 0.0;
        }

        return app(BookBasCalculator::class)->contributionForLabel(
            $transaction,
            $this->breakdownLabel,
        );
    }

    protected function setDefaultPeriod(): void
    {
        $today = now('Australia/Hobart');

        $financialYear = $this->financialYears->first(
            fn (BookFinancialYear $year): bool =>
                $today->toDateString() >= $year->start_date->toDateString()
                && $today->toDateString() <= $year->end_date->toDateString()
        ) ?? $this->financialYears->first();

        $this->financialYearFilter = $financialYear ? (string) $financialYear->id : '';

        if (! $financialYear) {
            $this->periodFilter = 'annual';

            return;
        }

        if (
            $today->toDateString() < $financialYear->start_date->toDateString()
            || $today->toDateString() > $financialYear->end_date->toDateString()
        ) {
            $this->periodFilter = 'annual';

            return;
        }

        $this->periodFilter = match (true) {
            $today->month >= 7 && $today->month <= 9 => 'q1',
            $today->month >= 10 => 'q2',
            $today->month <= 3 => 'q3',
            default => 'q4',
        };
    }

    protected function quarterLabels(): array
    {
        return [
            'Jul–Sep',
            'Oct–Dec',
            'Jan–Mar',
            'Apr–Jun',
        ];
    }

    protected function buildQuarterStatus(
        BookFinancialYear $financialYear,
        string $key,
        string $label,
        int $index,
        ?BookBasPeriod $saved,
    ): array {
        $start = $financialYear->start_date->copy()->addMonths($index * 3);
        $end = $start->copy()->addMonths(3)->subDay();

        if ($end->gt($financialYear->end_date)) {
            $end = $financialYear->end_date->copy();
        }

        $today = now('Australia/Hobart')->startOfDay();
        $dueDates = app(BookBasDueDateService::class);
        $dueDate = $dueDates->dueDate($end, $saved?->due_date);
        $warningStartsAt = $dueDates->warningStartsAt($dueDate);
        $daysUntilDue = $dueDates->daysUntil($dueDate, $today);

        if ($saved?->status === 'lodged') {
            $status = 'lodged';
        } elseif ($saved?->status === 'reopened') {
            $status = 'reopened';
        } elseif ($saved?->status === 'historical' || $financialYear->status === 'closed') {
            $status = 'historical';
        } elseif ($start->gt($today)) {
            $status = 'future';
        } elseif ($today->lte($end)) {
            $status = 'open';
        } elseif ($today->gt($dueDate)) {
            $status = 'overdue';
        } elseif ($today->gte($warningStartsAt)) {
            $status = 'due_soon';
        } else {
            $status = 'ready';
        }

        return [
            'key' => $key,
            'label' => $label,
            'start' => $start,
            'end' => $end,
            'due_date' => $dueDate,
            'warning_starts_at' => $warningStartsAt,
            'days_until_due' => $daysUntilDue,
            'days_overdue' => max(0, -$daysUntilDue),
            'status' => $status,
            'status_label' => match ($status) {
                'due_soon' => 'Due soon',
                'ready' => 'Ready',
                default => ucfirst($status),
            },
            'saved' => $saved,
            'whole_net' => $saved
                ? $this->wholeDollar((float) $saved->gst_1a) - $this->wholeDollar((float) $saved->gst_1b)
                : null,
        ];
    }

    protected function historyDateLabel(array $status, ?BookBasPeriod $saved): string
    {
        if ($saved?->status === 'lodged' && $saved->lodged_at) {
            return 'Lodged ' . $saved->lodged_at
                ->timezone('Australia/Hobart')
                ->format('j M Y');
        }

        if ($saved?->status === 'reopened' && $saved->lodged_at) {
            return 'Reopened · lodged '
                . $saved->lodged_at
                    ->timezone('Australia/Hobart')
                    ->format('j M Y');
        }

        if ($status['status'] === 'historical') {
            return 'Closed financial year';
        }

        if ($status['status'] === 'future') {
            return 'Starts ' . $status['start']->format('j M Y');
        }

        return 'Due ' . $status['due_date']->format('j M Y');
    }

    protected function quarterPeriodLabel(\Carbon\CarbonInterface $start, \Carbon\CarbonInterface $end): string
    {
        return $start->format('M') . '–' . $end->format('M Y');
    }

    protected function wholeDollar(float $value): int
    {
        return $value >= 0
            ? (int) floor($value)
            : -(int) floor(abs($value));
    }

    protected function emptyBas(): array
    {
        return [
            'g1_total_sales' => 0.0,
            'g2_export_sales' => 0.0,
            'g3_gst_free_sales' => 0.0,
            'g10_capital_purchases' => 0.0,
            'g11_non_capital_purchases' => 0.0,
            'gst_1a' => 0.0,
            'gst_1b' => 0.0,
            'net_amount' => 0.0,
            'counts' => array_fill_keys(array_keys(BookBasCalculator::LABELS), 0),
            'transaction_count' => 0,
            'excluded_count' => 0,
            'uncategorised_count' => 0,
            'whole' => [
                'g1_total_sales' => 0,
                'g2_export_sales' => 0,
                'g3_gst_free_sales' => 0,
                'g10_capital_purchases' => 0,
                'g11_non_capital_purchases' => 0,
                'gst_1a' => 0,
                'gst_1b' => 0,
                'net_amount' => 0,
            ],
        ];
    }
}
