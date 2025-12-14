<?php

namespace App\Livewire;

use App\Models\Invoice;
use Livewire\Component;

class InvoiceUnpaidWidget extends Component
{
    public int $count = 0;
    public int $overdue = 0;

    public function mount()
    {
        $invoices = Invoice::where('status', 'sent')->get();

        $this->count = $invoices->count();
        $this->overdue = $invoices->filter(fn ($i) => $i->due_date->isPast())->count();
    }

    public function render()
    {
        return view('livewire.invoice-unpaid-widget');
    }
}
