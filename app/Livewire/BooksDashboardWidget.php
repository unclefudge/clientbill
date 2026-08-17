<?php

namespace App\Livewire;

use App\Filament\Pages\Books\BooksBas;
use App\Models\BookBasPeriod;
use App\Models\BookFinancialYear;
use App\Models\BookImportRow;
use App\Models\BookTransaction;
use App\Services\Books\BookBasCalculator;
use App\Services\Books\BookBasDueDateService;
use Carbon\Carbon;
use Livewire\Component;

class BooksDashboardWidget extends Component
{
    public ?int $businessId = null;
    public string $businessName = '';
    public bool $gstRegistered = false;
    public string $periodLabel = '';
    public float $gstNet = 0;
    public int $gstNetWhole = 0;
    public int $needsReview = 0;
    public ?string $dueDate = null;
    public ?string $attentionStatus = null;
    public ?string $attentionText = null;

    public function mount(): void
    {
        $user = auth()->user();

        if (! $user?->canAccessBooks()) {
            return;
        }

        $business = $user->defaultBookBusiness;

        if (! $business || ! $user->canAccessBookBusiness($business)) {
            $business = $user->bookBusinesses()
                ->where('book_businesses.active', true)
                ->orderBy('book_businesses.name')
                ->first();
        }

        if (! $business) {
            return;
        }

        $this->businessId = $business->id;
        $this->businessName = $business->name;
        $this->gstRegistered = (bool) $business->gst_registered;

        $today = now('Australia/Hobart')->startOfDay();
        $attention = $this->basAttentionPeriod($business->id, $today);

        if ($attention) {
            $start = $attention['start'];
            $end = $attention['end'];
            $this->periodLabel = $attention['label'];

            $bas = app(BookBasCalculator::class)->calculate($business, $start, $end);
            $this->gstNet = (float) $bas['net_amount'];
            $this->gstNetWhole = (int) $bas['whole']['net_amount'];

            $this->dueDate = $attention['due_date']->format('j M Y');
            $this->attentionStatus = $attention['status'];

            $this->attentionText = $attention['status'] === 'overdue'
                ? $attention['days_overdue'] . ' ' . str('day')->plural($attention['days_overdue']) . ' overdue · was due ' . $this->dueDate
                : 'Due in ' . $attention['days_until_due'] . ' ' . str('day')->plural($attention['days_until_due']) . ' · ' . $this->dueDate;
        } else {
            [$start, $end, $label] = $this->quarterBounds($today);
            $this->periodLabel = $label;

            $bas = app(BookBasCalculator::class)->calculate($business, $start, $end);
            $this->gstNet = (float) $bas['net_amount'];
            $this->gstNetWhole = (int) $bas['whole']['net_amount'];
        }

        $uncategorised = BookTransaction::query()
            ->where('book_business_id', $business->id)
            ->whereNull('book_category_id')
            ->count();

        $pendingImports = BookImportRow::query()
            ->whereHas('import', fn ($query) => $query->where('book_business_id', $business->id))
            ->where('status', 'pending')
            ->count();

        $this->needsReview = $uncategorised + $pendingImports;
    }

    protected function basAttentionPeriod(int $businessId, Carbon $today): ?array
    {
        $financialYear = BookFinancialYear::query()
            ->where('book_business_id', $businessId)
            ->where('status', 'open')
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereDate('end_date', '>=', $today->toDateString())
            ->first();

        if (! $financialYear) {
            return null;
        }

        $savedPeriods = BookBasPeriod::query()
            ->where('book_business_id', $businessId)
            ->where('book_financial_year_id', $financialYear->id)
            ->where('period_type', 'quarterly')
            ->get()
            ->keyBy(fn (BookBasPeriod $period): string => $period->start_date->toDateString());

        $dueDates = app(BookBasDueDateService::class);
        $labels = ['Jul–Sep', 'Oct–Dec', 'Jan–Mar', 'Apr–Jun'];
        $candidates = [];

        foreach ($labels as $index => $label) {
            $start = $financialYear->start_date->copy()->addMonths($index * 3);
            $end = $start->copy()->addMonths(3)->subDay();

            if ($today->lte($end)) {
                continue;
            }

            $saved = $savedPeriods->get($start->toDateString());

            if ($saved?->status === 'lodged') {
                continue;
            }

            // A reopened BAS was already lodged once and is an amendment workflow,
            // so do not call it overdue on the main dashboard.
            if ($saved?->status === 'reopened') {
                continue;
            }

            $dueDate = $dueDates->dueDate($end, $saved?->due_date);
            $warningStarts = $dueDates->warningStartsAt($dueDate);

            if ($today->gt($dueDate)) {
                $daysUntilDue = $dueDates->daysUntil($dueDate, $today);

                $candidates[] = [
                    'status' => 'overdue',
                    'start' => $start,
                    'end' => $end,
                    'label' => $label . ' ' . $end->year,
                    'due_date' => $dueDate,
                    'days_until_due' => $daysUntilDue,
                    'days_overdue' => abs($daysUntilDue),
                ];

                continue;
            }

            if ($today->gte($warningStarts)) {
                $daysUntilDue = max(0, $dueDates->daysUntil($dueDate, $today));

                $candidates[] = [
                    'status' => 'due_soon',
                    'start' => $start,
                    'end' => $end,
                    'label' => $label . ' ' . $end->year,
                    'due_date' => $dueDate,
                    'days_until_due' => $daysUntilDue,
                    'days_overdue' => 0,
                ];
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, function (array $a, array $b): int {
            $priority = ['overdue' => 0, 'due_soon' => 1];

            return [$priority[$a['status']], $a['due_date']->timestamp]
                <=> [$priority[$b['status']], $b['due_date']->timestamp];
        });

        return $candidates[0];
    }

    protected function quarterBounds(Carbon $date): array
    {
        $year = $date->year;
        $month = $date->month;

        if ($month >= 7 && $month <= 9) {
            return [Carbon::create($year, 7, 1), Carbon::create($year, 9, 30), 'Jul–Sep ' . $year];
        }

        if ($month >= 10) {
            return [Carbon::create($year, 10, 1), Carbon::create($year, 12, 31), 'Oct–Dec ' . $year];
        }

        if ($month <= 3) {
            return [Carbon::create($year, 1, 1), Carbon::create($year, 3, 31), 'Jan–Mar ' . $year];
        }

        return [Carbon::create($year, 4, 1), Carbon::create($year, 6, 30), 'Apr–Jun ' . $year];
    }

    public function render()
    {
        return view('livewire.books-dashboard-widget', [
            'booksUrl' => BooksBas::getUrl(),
        ]);
    }
}
