<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\TimeEntry;
use Carbon\Carbon;

class InvoiceHoursWidget extends Component
{
    public function render()
    {
        $monthStart = Carbon::now()->startOfMonth();
        $monthEnd   = Carbon::now()->endOfMonth();

        $total = TimeEntry::whereBetween('date', [$monthStart, $monthEnd])->sum('duration') / 60;
        $unbilled = TimeEntry::whereNull('invoice_id')->whereBetween('date', [$monthStart, $monthEnd])->sum('duration') / 60;

        return view('livewire.invoice-hours-widget', compact('total', 'unbilled'));
    }
}
