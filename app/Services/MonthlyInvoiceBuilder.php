<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyInvoiceBuilder
{
    public function buildForMonth(Client $client, int $year, int $month): Invoice
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth();

        return DB::transaction(function () use ($client, $start, $end) {

            // 1) Create the invoice
            $invoice = Invoice::create([
                'client_id'  => $client->id,
                'issue_date' => $start->copy()->addMonth()->format('Y-m-d'),
                'status'     => 'draft',
            ]);
            $invoice->generateMissingSummaries();

            // 2) Fetch relevant time entries
            $entries = TimeEntry::where('project_id', $client->projects->pluck('id'))
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->whereIn('entry_type', ['regular', 'prebill'])   // exclude payback
                ->whereNull('invoice_id')
                ->orderBy('date')
                ->get();

            if ($entries->isEmpty()) {
                return $invoice; // create empty invoice, your choice
            }

            // 3) Group entries by project
            $groups = $entries->groupBy('project_id');

            foreach ($groups as $projectId => $items) {
                $project = $items->first()->project;

                // Total hours
                $totalHours = $items->sum(fn ($e) => $e->duration / 60);

                // Build summary text
                $summaryLines = $items->map(function ($e) {
                    return "- " . ($e->summary ?: $e->activity);
                })->unique();

                $summary = implode("\n", $summaryLines->toArray());

                // Create line item
                InvoiceItem::create([
                    'invoice_id'  => $invoice->id,
                    'project_id'  => $projectId,
                    'type'        => 'time',
                    'description' => $project->name . "\n" . $summary,
                    'quantity'    => round($totalHours, 2),
                    'rate'        => $project->rate ?? $client->rate,
                ]);

                // Mark entries as billed
                foreach ($items as $entry) {
                    $entry->invoice_id = $invoice->id;
                    $entry->save();
                }
            }

            // 4) Recalculate invoice totals
            $invoice->recalculateTotal();

            return $invoice;
        });
    }
}
