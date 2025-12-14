<?php

namespace App\Console\Commands;

use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BuildInvoicesForYear extends Command
{
    protected $signature = 'invoices:build {client_id} {year}';
    protected $description = 'Generate monthly invoices for a client for an entire year';

    public function handle()
    {
        $client = Client::findOrFail($this->argument('client_id'));
        $year   = intval($this->argument('year'));

        for ($month = 1; $month <= 12; $month++) {
            $invoice = $client->buildInvoiceForMonth($year, $month);

            $this->info("Invoices for {$year}-{$month} created: #{$invoice->id}");
        }

        return Command::SUCCESS;
    }
}
