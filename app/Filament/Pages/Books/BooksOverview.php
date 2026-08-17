<?php

namespace App\Filament\Pages\Books;

use App\Models\BookBasPeriod;
use App\Models\BookImportRow;
use App\Models\BookTransaction;
use BackedEnum;
use Carbon\Carbon;
use Filament\Support\Icons\Heroicon;

class BooksOverview extends BookPage
{
    protected string $view = 'filament.pages.books.overview';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
    protected static ?string $navigationLabel = 'Books';
    protected static ?string $slug = 'books';
    protected static ?int $navigationSort = 4;
    protected static bool $shouldRegisterNavigation = true;

    public float $incomeYtd = 0;
    public float $expensesYtd = 0;
    public float $profitYtd = 0;
    public int $uncategorisedCount = 0;
    public int $pendingImportCount = 0;
    public float $gstCollected = 0;
    public float $gstCredits = 0;
    public float $gstNet = 0;
    public ?string $basPeriodLabel = null;
    public ?string $basDueDate = null;

    public function mount(): void
    {
        $this->mountBookContext();
        $this->loadData();
    }

    protected function bookBusinessChanged(): void
    {
        $this->loadData();
    }

    public function loadData(): void
    {
        $business = $this->getBookBusiness();
        [$fyStart, $fyEnd] = $this->financialYearBounds(now());

        $transactions = BookTransaction::query()
            ->where('book_business_id', $business->id)
            ->whereBetween('transaction_date', [$fyStart, $fyEnd]);

        $this->incomeYtd = (float) (clone $transactions)
            ->where('amount', '>', 0)
            ->selectRaw('COALESCE(SUM(COALESCE(business_amount, amount)), 0) as total')
            ->value('total');

        $this->expensesYtd = abs((float) (clone $transactions)
            ->where('amount', '<', 0)
            ->selectRaw('COALESCE(SUM(COALESCE(business_amount, amount)), 0) as total')
            ->value('total'));

        $this->profitYtd = $this->incomeYtd - $this->expensesYtd;

        $this->uncategorisedCount = BookTransaction::query()
            ->where('book_business_id', $business->id)
            ->whereNull('book_category_id')
            ->count();

        $this->pendingImportCount = BookImportRow::query()
            ->whereHas('import', fn ($query) => $query->where('book_business_id', $business->id))
            ->where('status', 'pending')
            ->count();

        [$quarterStart, $quarterEnd, $quarterLabel] = $this->quarterBounds(now());
        $this->basPeriodLabel = $quarterLabel;

        $quarterTransactions = BookTransaction::query()
            ->where('book_business_id', $business->id)
            ->whereBetween('transaction_date', [$quarterStart, $quarterEnd]);

        $this->gstCollected = (float) (clone $quarterTransactions)
            ->where('amount', '>', 0)
            ->sum('gst_amount');

        $this->gstCredits = abs((float) (clone $quarterTransactions)
            ->where('amount', '<', 0)
            ->sum('gst_amount'));

        $this->gstNet = $this->gstCollected - $this->gstCredits;

        $savedBas = BookBasPeriod::query()
            ->where('book_business_id', $business->id)
            ->whereDate('start_date', $quarterStart)
            ->whereDate('end_date', $quarterEnd)
            ->first();

        $this->basDueDate = $savedBas?->due_date?->format('j M Y');
    }

    protected function financialYearBounds(Carbon $date): array
    {
        $year = $date->month >= 7 ? $date->year : $date->year - 1;

        return [
            Carbon::create($year, 7, 1)->startOfDay(),
            Carbon::create($year + 1, 6, 30)->endOfDay(),
        ];
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
}
