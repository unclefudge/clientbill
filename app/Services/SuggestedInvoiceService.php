<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Hosting;
use App\Models\TimeEntry;
use Carbon\Carbon;

class SuggestedInvoiceService
{
    public function build(int $days = 30): array
    {
        $today = Carbon::today()->tz('Australia/Tasmania');
        $tomorrow = $today->copy()->addDays(1);
        $soon = $today->copy()->addDays($days);

        // Preload clients → projects → time entries
        $clients = Client::with(['projects.timeEntries'])->get();

        $results = [];

        foreach ($clients as $client) {

            $projectIds = $client->projects->pluck('id')->all();

            // UNBILLED HOURS
            $unbilledMinutes = 0;
            $entries = TimeEntry::whereIn('project_id', $projectIds)->whereNull('invoice_id')->get(); //->sum('duration') / 60;
            foreach ($entries as $entry) {
                if ($entry->entry_type != 'payback')
                    $unbilledMinutes = $unbilledMinutes + $entry->duration;
            }
            $unbilledHours = $unbilledMinutes / 60;

            // RENEWALS
            $pastDomainRenewals = Domain::forClientRenewingBetween($client->id, $client->lastInvoiceDate(), $today);
            $pastHostingRenewals = Hosting::forClientRenewingBetween($client->id, $client->lastInvoiceDate(), $today);
            $soonDomainRenewals = Domain::forClientRenewingBetween($client->id, $tomorrow, $soon);
            $soonHostingRenewals = Hosting::forClientRenewingBetween($client->id, $tomorrow, $soon);

            $pastRenewalCount = $pastDomainRenewals->count() + $pastHostingRenewals->count();
            $totalRenewalCount = $pastDomainRenewals->count() + $pastHostingRenewals->count() + $soonDomainRenewals->count() + $soonHostingRenewals->count();

            // HOUR BALANCE
            $hourBalance = $client->hourBalance();
            $hourBalanceLabel = $client->hourBalanceLabel();

            // BUILD SUGGESTION TEXT
            $suggest = "No action required.";
            $canInvoice = false;

            if ($unbilledHours > 0) {
                $suggest = "You have {$unbilledHours} unbilled hours — invoice recommended.";
                $button = 'Invoice';
                $canInvoice = true;
            }

            if ($pastRenewalCount > 0) {
                $suggest = "You have {$pastRenewalCount} unbilled renewals — invoice recommended.";
                $button = 'Invoice';
                $canInvoice = true;
            } else if ($totalRenewalCount > 0) {
                $suggest = "Upcoming renewals ({$totalRenewalCount}) — invoice soon.";
                $button = 'Can invoice';
                $canInvoice = true;
            }

            // Cape Cod monthly example
            if ($client->id == 1) {
                $lastMonthEntries = TimeEntry::whereIn('project_id', $projectIds)->whereNull('invoice_id')->whereDate('date', '<', $today->startOfMonth())->get();
                if ($lastMonthEntries) {
                    $suggest = "Monthly client — invoice for past month.";
                    $button = 'Invoice';
                    $canInvoice = true;
                } else if ($unbilledHours > 0) {
                    $suggest = "Monthly client — invoice for this month.";
                    $button = 'Can invoice';
                    $canInvoice = true;
                }
            }

            $results[] = [
                'client_id'        => $client->id,
                'client_name'      => $client->name,
                'unbilled_minutes' => $unbilledMinutes,
                'unbilled_hours'   => $unbilledHours,
                'renewals'         => $totalRenewalCount,
                'hour_balance'     => $hourBalance,
                'hour_balance_label' => $hourBalanceLabel,
                'suggestion'       => $suggest,
                'button'           => $button,
                'can_invoice'      => $canInvoice,
                'entries'          => $entries->toArray(),
            ];
        }

        return $results;
    }
}
