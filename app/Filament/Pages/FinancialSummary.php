<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\InvoiceItem;
use App\Support\FinancialYear;
use Carbon\Carbon;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

class FinancialSummary extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
    protected static ?string $navigationLabel = 'Financial Summary';
    protected static UnitEnum|string|null $navigationGroup = 'Admin';
    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.financial-summary';

    /** UI state */
    public string $sortBy = 'year'; // year | total
    public ?int $clientId = null;

    /** Data */
    public array $clients = [];
    public array $rawFinancialYears = [];
    public array $financialYears = [];
    public array $expandedYears = [];
    public float $maxTotal = 0;
    public bool $hoursOnly = false;

    public function mount(): void
    {
        $this->clients = Client::where('active', 1)->orderBy('name')->get()->toArray();
        $this->reload();
    }

    public function reload(): void
    {
        $this->rawFinancialYears = $this->buildFinancialSummary();
        $this->applySorting();
    }

    protected function buildFinancialSummary(): array
    {
        $items = InvoiceItem::whereHas('invoice', function ($q) {
            $q->where('status', 'paid');
            if ($this->clientId) {
                $q->where('client_id', $this->clientId);
            }
        })
            ->with('invoice')
            ->get();

        $years = [];

        foreach ($items as $item) {

            // If "Hours only" toggle is on, skip non-time items
            if ($this->hoursOnly && !$item->time_entry_id) {
                continue;
            }

            $invoice = $item->invoice;

            $date =
                $invoice->issue_date
                && Carbon::parse($invoice->issue_date)->year > 2000
                    ? $invoice->issue_date
                    : ($invoice->paid_at ?? $invoice->created_at);

            if (!$date) continue;

            $fy = $this->financialYearFromDate(Carbon::parse($date));
            $amount = round(($item->quantity ?? 1) * ($item->rate ?? 0), 2);

            if (!isset($years[$fy])) {
                $years[$fy] = [
                    'total'      => 0,
                    'hours'      => 0,
                    'domains'    => 0,
                    'hosting'    => 0,
                    'additional' => 0,
                    'change'     => null,
                ];
            }

            // ADD TO TOTAL ONCE
            $years[$fy]['total'] += $amount;

            // ADD TO EXACTLY ONE CATEGORY
            if ($item->time_entry_id) {
                $years[$fy]['hours'] += $amount;
            }
            elseif (!$this->hoursOnly) {
                if ($item->type === 'domain') {
                    $years[$fy]['domains'] += $amount;
                }
                elseif ($item->type === 'hosting') {
                    $years[$fy]['hosting'] += $amount;
                }
                elseif ($item->type === 'custom') {
                    $years[$fy]['additional'] += $amount;
                }
            }
        }

        // % change
        ksort($years);
        $prev = null;
        foreach ($years as &$row) {
            if ($prev !== null && $prev > 0) {
                $row['change'] = round((($row['total'] - $prev) / $prev) * 100, 1);
            }
            $prev = $row['total'];
        }

        return $years;
    }


    protected function applySorting(): void
    {
        $years = $this->rawFinancialYears;

        if ($this->sortBy === 'total') {
            uasort($years, fn ($a, $b) => $b['total'] <=> $a['total']);
        } else {
            krsort($years); // newest first
        }

        $this->maxTotal = max(array_column($years, 'total')) ?: 1;
        $this->financialYears = $years;
        $this->expandedYears = [];
    }

    public function setSort(string $sort): void
    {
        $this->sortBy = $sort;
        $this->applySorting();
    }

    public function updatedClientId(): void
    {
        $this->reload();
    }

    public function toggleYear(string $year): void
    {
        if (in_array($year, $this->expandedYears, true)) {
            $this->expandedYears = array_values(array_diff($this->expandedYears, [$year]));
        } else {
            $this->expandedYears[] = $year;
        }
    }

    public function toggleHoursOnly(): void
    {
        $this->hoursOnly = ! $this->hoursOnly;
        $this->reload();
    }

    public function financialYearFromDate(Carbon $date): string
    {
        $year = $date->year;

        return $date->month < 7
            ? ($year - 1) . '/' . $year
            : $year . '/' . ($year + 1);
    }
}
