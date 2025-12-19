<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Hosting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceBuilderService
{
    /**
     * Shared logic used by both PREVIEW + CREATE.
     */
    protected function prepareInvoiceData(array $data): array
    {
        $client   = Client::with('projects')->findOrFail($data['client_id']);
        $projects = $client->projects;
        $projectIds = $projects->pluck('id')->all();

        $today = Carbon::now("Australia/Tasmania");

        // -------------------------------------------------------------
        // Determine invoicing period
        // -------------------------------------------------------------
        switch ($data['period']) {

            // All unbilled entries (we'll use 2000-01-01)
            case 'unbilled':
                $start = Carbon::parse('2000-01-01');
                $end   = $today;
                break;

            // Everything since last invoice
            case 'last_invoice':
                $lastInvoice = Invoice::where('client_id', $client->id)->orderBy('issue_date', 'desc')->first();
                break;

            // A specific month (great for Cape Cod)
            case 'month':
                $start = Carbon::create($data['year'], $data['month'], 1);
                $end   = $start->copy()->endOfMonth();
                break;

            // Custom range
            case 'custom':
                $start = Carbon::parse($data['start']);
                $end   = Carbon::parse($data['end']);
                break;

            default:
                throw new \Exception("Invalid period in invoice builder");
        }


        // -------------------------------------------------------------
        // Fetch billable hours
        // -------------------------------------------------------------
        $entries = TimeEntry::with('project')
            ->whereIn('project_id', $projectIds)
            ->whereBetween('date', [$start, $end])
            ->where(function($q){
                $q->whereNull('invoice_id')->orWhere('invoice_id', 0);
            })
            ->where('billable', true)
            ->orderBy('date')
            ->get();

        // -------------------------------------------------------------
        // Hosting + Domains
        // -------------------------------------------------------------
        $hosting = Hosting::forClientRenewingBetween($client->id, $start, $end);
        $domains = Domain::forClientRenewingBetween($client->id, $start, $end);

        // -------------------------------------------------------------
        // Build structured invoice rows
        // -------------------------------------------------------------
        $projectRows = [];
        $subtotal    = 0;

        foreach ($entries->groupBy('project_id') as $projectId => $rows) {
            $project = $projects->where('id', $projectId)->first();
            $rate    = $project->rate ?? $client->rate;

            $totalMinutes = $rows->sum(function ($entry) {
                return match ($entry->type) {
                    'payback' => -1 * $entry->duration,
                    'regular', 'prebill' => $entry->duration,
                    default => 0,
                };
            });

            $hours  = round($totalMinutes / 60, 2);
            $amount = $hours * $rate;

            $projectRows[] = [
                'project_id'      => $projectId,
                'project_name'    => $project->name,
                'rate'            => $rate,
                'qty'             => $hours,
                'total'           => $amount,
                'summary_bullets' => $rows->pluck('summary')->filter()->values(),
            ];

            $subtotal += $amount;
        }

        $hostingRows = [];
        foreach ($hosting as $h) {
            $hostingRows[] = [
                'id'          => $h->id,
                'description' => $h->description,
                'summary'     => $h->summary,
                'rate'        => $h->rate,
                'quantity'    => 1,
                'total'       => $h->rate,
            ];
            $subtotal += $h->rate;
        }

        // DOMAIN can be merged as one row if multiple same-year renewals
        $domainRow = null;
        if ($domains->isNotEmpty()) {
            $min = $domains->min('rate');
            $max = $domains->max('rate');

            $domainRow = [
                'description' => "Domain Renewals",
                'summary'     => null,
                'rateMin'     => $min,
                'rateMax'     => $max,
                'quantity'    => $domains->count(),
                'total'       => $domains->sum('rate')
            ];

            $subtotal += $domainRow['total'];
        }

        $gst   = round($subtotal * 0.10, 2);
        $total = round($subtotal + $gst, 2);

        return [
            'client'       => $client,
            'start'        => $start,
            'end'          => $end,
            'items'        => [
                'projects' => $projectRows,
                'hosting'  => $hostingRows,
                'domains'  => $domainRow,
            ],
            'subtotal'     => $subtotal,
            'gst'          => $gst,
            'total'        => $total,
            'hostingModels'=> $hosting,
            'domainModels' => $domains,
            'projectEntries'=> $entries,
        ];
    }

    /**
     * PREVIEW INVOICE — no DB writes.
     */
    public function preview(array $data): array
    {
        $raw = $this->prepareInvoiceData($data);

        // Build virtual invoice object
        $fake          = new \stdClass();
        $fake->id      = null; // preview → no ID
        $fake->client  = $raw['client'];
        $fake->issue_date = now();
        $fake->due_date   = now()->copy()->addDays(7);
        $fake->subtotal   = $raw['subtotal'];
        $fake->gst        = $raw['gst'];
        $fake->total      = $raw['total'];

        return [
            'invoice' => $fake,
            'items'   => $raw['items'],
        ];
    }

    /**
     * CREATE INVOICE — actual DB writes.
     */
    public function create(array $data): Invoice
    {
        $raw = $this->prepareInvoiceData($data);

        return DB::transaction(function () use ($raw) {

            $invoice = Invoice::create([
                'client_id'  => $raw['client']->id,
                'issue_date' => now(),
                'due_date'   => now()->copy()->addDays(7),
                'status'     => 'draft',
            ]);

            // Projects (TimeEntries)
            foreach ($raw['projectEntries'] as $entry) {
                $rate = $entry->rate ?? $entry->project->rate ?? $raw['client']->rate;
                $hours = $entry->duration / 60;

                InvoiceItem::create([
                    'invoice_id'    => $invoice->id,
                    'time_entry_id' => $entry->id,
                    'type'          => 'time',
                    'description'   => $entry->project->name,
                    'quantity'      => $hours,
                    'rate'          => $rate,
                ]);

                $entry->invoice_id = $invoice->id;
                $entry->save();
            }

            // Hosting
            foreach ($raw['hostingModels'] as $h) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'hosting_id' => $h->id,
                    'type'       => 'hosting',
                    'description'=> $h->description,
                    'summary'    => $h->summary,
                    'quantity'   => 1,
                    'rate'       => $h->rate,
                ]);

                $h->last_renewed = $h->next_renewal;
                $h->syncNextRenewalFromLast();
                $h->save();
            }

            // Domains
            foreach ($raw['domainModels'] as $d) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'domain_id'  => $d->id,
                    'type'       => 'domain',
                    'description'=> $d->name,
                    'summary'    => $d->summary,
                    'quantity'   => 1,
                    'rate'       => $d->rate,
                ]);

                $d->last_renewed = $d->next_renewal;
                $d->syncNextRenewalFromLast();
                $d->save();
            }

            $invoice->recalculateTotal();

            return $invoice;
        });
    }
}
