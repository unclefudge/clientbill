<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Domain;
use Carbon\Carbon;

class InvoiceRenewalsWidget extends Component
{
    public int $renewals = 0;

    public function mount()
    {
        $now = Carbon::now()->startOfDay();
        $in30 = Carbon::now()->addDays(30)->endOfDay();

        // Count domains renewing within the next 30 days
        $this->renewals = Domain::whereBetween('renewal', [$now, $in30])->count();
    }

    public function render()
    {
        return view('livewire.invoice-renewals-widget');
    }
}
