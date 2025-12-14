<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Client;
use App\Models\Domain;
use Carbon\Carbon;

class InvoiceClientAlertsWidget extends Component
{
    public int $alerts = 0;

    public function mount()
    {
        $this->alerts = $this->calculateClientAlerts();
    }

    private function calculateClientAlerts(): int
    {
        $alertCount = 0;

        // Load all clients with projects+timeEntries
        $clients = Client::with(['projects.timeEntries'])->get();

        foreach ($clients as $client) {

            $hourBalance = $client->hourBalance();   // uses your model function
            $unbilledHours = $client->timeEntries->whereNull('invoice_id')->sum('duration') / 60;

            // Domain renewals within 30 days
            $renewals = Domain::where('client_id', $client->id)
                ->whereBetween('renewal', [
                    Carbon::now(),
                    Carbon::now()->addDays(30)
                ])
                ->count();

            // --- Alert Rules (customizable) ---
            if (
                $hourBalance < -5     ||   // Client owes you hours
                $hourBalance > 5      ||   // You owe client hours
                $unbilledHours >= 10  ||   // Lots of unbilled hours
                $renewals > 0              // Upcoming renewal
            ) {
                $alertCount++;
            }
        }

        return $alertCount;
    }

    public function render()
    {
        return view('livewire.invoice-client-alerts-widget');
    }
}
