<?php

namespace Database\Seeders;

use App\Models\BookBankAccount;
use App\Models\BookBusiness;
use App\Models\BookCategory;
use App\Models\BookFinancialYear;
use App\Models\BookTransaction;
use App\Models\Client;
use App\Models\Domain;
use App\Models\Hosting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceProjectSummary;
use App\Models\Product;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PortfolioDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('PortfolioDemoSeeder must never be run in production.');
        }

        if (User::query()->exists() || Client::query()->exists() || BookBusiness::query()->exists()) {
            throw new RuntimeException(
                'PortfolioDemoSeeder requires a fresh, empty database. Create a separate portfolio database and run migrate:fresh first.'
            );
        }

        DB::transaction(function (): void {
            $today = now('Australia/Hobart')->startOfDay();

            $user = User::query()->create([
                'name' => 'Jordan Taylor',
                'email' => 'portfolio@example.com',
                'password' => Hash::make('password'),
                'active' => true,
                'can_access_billing' => true,
                'can_manage_users' => true,
                'default_area' => 'billing',
                'theme_mode' => 'dark',
            ]);

            $clients = $this->createClientsAndProjects();
            $this->createProducts();
            $this->createRenewals($clients, $today);
            $entries = $this->createTimeEntries($clients, $today);
            $this->createInvoices($clients, $entries, $today);
            $this->createBooksData($user, $today);
        });

        $this->command?->newLine();
        $this->command?->info('Portfolio demonstration data created.');
        $this->command?->line('Login: portfolio@example.com');
        $this->command?->line('Password: password');
        $this->command?->warn('Use this data only in a separate local portfolio database.');
    }

    private function createClientsAndProjects(): array
    {
        $definitions = [
            ['name' => 'Harbour Build Co.', 'code' => 'HBC', 'contact' => 'Emily Hart', 'suburb' => 'Hobart', 'rate' => 125, 'projects' => ['Operations Platform', 'Zoho Integration']],
            ['name' => 'Riverstone Events', 'code' => 'RSE', 'contact' => 'Daniel Brooks', 'suburb' => 'Launceston', 'rate' => 120, 'projects' => ['Venue Booking', 'Reporting & Automation']],
            ['name' => 'Northbank Architecture', 'code' => 'NBA', 'contact' => 'Mia Campbell', 'suburb' => 'Melbourne', 'rate' => 130, 'projects' => ['Client Portal']],
            ['name' => 'Summit Electrical', 'code' => 'SEL', 'contact' => 'Oliver Grant', 'suburb' => 'Devonport', 'rate' => 120, 'projects' => ['Compliance Workflow']],
            ['name' => 'Wildfern Consulting', 'code' => 'WFC', 'contact' => 'Sophie Nguyen', 'suburb' => 'Sydney', 'rate' => 125, 'projects' => ['Laravel Support']],
            ['name' => 'Tasman Legal Services', 'code' => 'TLS', 'contact' => 'Noah Reid', 'suburb' => 'Hobart', 'rate' => 135, 'projects' => ['Document Automation']],
        ];

        $clients = [];

        foreach ($definitions as $order => $definition) {
            $client = Client::query()->create([
                'name' => $definition['name'],
                'code' => $definition['code'],
                'contact' => $definition['contact'],
                'email' => strtolower($definition['code']).'@example.com',
                'phone' => sprintf('0400 000 %03d', $order + 1),
                'address' => (20 + $order).' Example Street',
                'suburb' => $definition['suburb'],
                'state' => $definition['suburb'] === 'Melbourne' ? 'VIC' : ($definition['suburb'] === 'Sydney' ? 'NSW' : 'TAS'),
                'postcode' => '7000',
                'rate' => $definition['rate'],
                'order' => $order + 1,
                'active' => true,
                'notes' => 'Fictional portfolio demonstration client.',
            ]);

            $projects = [];
            foreach ($definition['projects'] as $name) {
                $projects[] = Project::query()->create([
                    'client_id' => $client->id,
                    'name' => $name,
                    'description' => 'Fictional project created for portfolio screenshots.',
                    'rate' => $definition['rate'],
                    'active' => true,
                ]);
            }

            $clients[$definition['code']] = ['model' => $client, 'projects' => $projects];
        }

        return $clients;
    }

    private function createProducts(): void
    {
        foreach ([
            ['name' => 'Managed web hosting — annual', 'rate' => 360, 'order' => 1],
            ['name' => 'Domain registration — annual', 'rate' => 42, 'order' => 2],
            ['name' => 'SSL certificate management', 'rate' => 95, 'order' => 3],
            ['name' => 'Priority support allocation', 'rate' => 480, 'order' => 4],
        ] as $product) {
            Product::query()->create($product + ['active' => true]);
        }
    }

    private function createRenewals(array $clients, Carbon $today): void
    {
        $renewals = [
            ['client' => 'HBC', 'domain' => 'harbourbuild.example', 'offset' => 9, 'hosting' => true],
            ['client' => 'RSE', 'domain' => 'riverstoneevents.example', 'offset' => 18, 'hosting' => true],
            ['client' => 'NBA', 'domain' => 'northbankstudio.example', 'offset' => 27, 'hosting' => false],
            ['client' => 'SEL', 'domain' => 'summitelectrical.example', 'offset' => 74, 'hosting' => true],
            ['client' => 'WFC', 'domain' => 'wildfern.example', 'offset' => 132, 'hosting' => true],
        ];

        foreach ($renewals as $order => $renewal) {
            $client = $clients[$renewal['client']]['model'];
            $nextRenewal = $today->copy()->addDays($renewal['offset']);

            Domain::query()->create([
                'client_id' => $client->id,
                'name' => $renewal['domain'],
                'description' => 'Domain registration and DNS management',
                'summary' => 'Annual domain renewal',
                'date' => $nextRenewal->copy()->subYears(3),
                'last_renewed' => $nextRenewal->copy()->subYear(),
                'next_renewal' => $nextRenewal,
                'rate' => 42,
                'renewal' => 1,
                'order' => $order + 1,
                'active' => true,
            ]);

            if ($renewal['hosting']) {
                Hosting::query()->create([
                    'client_id' => $client->id,
                    'name' => 'Managed application hosting',
                    'domain' => $renewal['domain'],
                    'description' => 'Managed cloud hosting, monitoring and backups',
                    'summary' => 'Annual managed hosting',
                    'date' => $nextRenewal->copy()->subYears(2),
                    'last_renewed' => $nextRenewal->copy()->subYear(),
                    'next_renewal' => $nextRenewal,
                    'renewal' => 1,
                    'rate' => 360,
                    'ssl' => true,
                    'order' => $order + 1,
                    'active' => true,
                ]);
            }
        }
    }

    private function createTimeEntries(array $clients, Carbon $today): array
    {
        $activities = [
            'HBC' => ['Planner workflow improvements', 'Zoho enquiry automation', 'Scheduled reporting review', 'Livewire component updates'],
            'RSE' => ['Event booking workflow', 'Catering interface improvements', 'Labour costing adjustments', 'Operational report development'],
            'NBA' => ['Client portal permissions', 'Document upload improvements', 'Dashboard refinements'],
            'SEL' => ['Compliance reminder workflow', 'Inspection form updates', 'Email notification testing'],
            'WFC' => ['Laravel version upgrade', 'Queue monitoring', 'Application maintenance'],
            'TLS' => ['Document generation workflow', 'Approval automation', 'Template refinements'],
        ];
        $durations = [60, 90, 120, 150, 180, 210, 240];
        $entries = [];

        foreach ($clients as $code => $data) {
            foreach (range(0, 7) as $index) {
                $project = $data['projects'][$index % count($data['projects'])];
                $duration = $durations[($index + strlen($code)) % count($durations)];
                $date = $today->copy()->subDays(($index * 3) + array_search($code, array_keys($clients), true));

                $entry = TimeEntry::query()->create([
                    'project_id' => $project->id,
                    'activity' => $activities[$code][$index % count($activities[$code])],
                    'date' => $date,
                    'start' => '09:00:00',
                    'end' => $date->copy()->setTime(9, 0)->addMinutes($duration)->format('H:i:s'),
                    'duration' => $duration,
                    'rate' => $project->rate,
                    'entry_type' => 'regular',
                    'billable' => true,
                    'notes' => $activities[$code][$index % count($activities[$code])],
                ]);

                $entries[$code][] = $entry;
            }
        }

        return $entries;
    }

    private function createInvoices(array $clients, array $entries, Carbon $today): void
    {
        $definitions = [
            ['client' => 'HBC', 'days' => -62, 'status' => 'paid', 'paid' => -45, 'entry_indexes' => [6, 7]],
            ['client' => 'RSE', 'days' => -35, 'status' => 'paid', 'paid' => -20, 'entry_indexes' => [5, 6, 7]],
            ['client' => 'NBA', 'days' => -24, 'status' => 'sent', 'due' => -10, 'entry_indexes' => [4, 5]],
            ['client' => 'SEL', 'days' => -8, 'status' => 'sent', 'due' => 6, 'entry_indexes' => [5, 6]],
            ['client' => 'WFC', 'days' => -3, 'status' => 'draft', 'due' => 11, 'entry_indexes' => [6, 7]],
        ];

        foreach ($definitions as $definition) {
            $client = $clients[$definition['client']]['model'];
            $issueDate = $today->copy()->addDays($definition['days']);
            $invoice = Invoice::query()->create([
                'client_id' => $client->id,
                'issue_date' => $issueDate,
                'due_date' => isset($definition['due']) ? $today->copy()->addDays($definition['due']) : $issueDate->copy()->addDays(14),
                'paid_date' => isset($definition['paid']) ? $today->copy()->addDays($definition['paid']) : null,
                'status' => $definition['status'],
                'notes' => 'Thank you for your business.',
            ]);

            $usedProjects = [];
            foreach ($definition['entry_indexes'] as $order => $entryIndex) {
                $entry = $entries[$definition['client']][$entryIndex];
                $entry->update(['invoice_id' => $invoice->id]);
                $rate = $entry->rate ?? $client->rate;

                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'type' => 'time',
                    'description' => $entry->activity,
                    'quantity' => round($entry->duration / 60, 2),
                    'rate' => $rate,
                    'time_entry_id' => $entry->id,
                    'order' => $order + 1,
                ]);
                $usedProjects[$entry->project_id][] = $entry->activity;
            }

            if ($definition['client'] === 'HBC') {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'type' => 'custom',
                    'description' => 'Workflow discovery and planning session',
                    'summary' => 'Requirements review and implementation planning',
                    'quantity' => 1,
                    'rate' => 450,
                    'order' => 20,
                ]);
            }

            foreach ($usedProjects as $projectId => $summary) {
                InvoiceProjectSummary::query()->create([
                    'invoice_id' => $invoice->id,
                    'project_id' => $projectId,
                    'summary' => implode("\n", array_unique($summary)),
                ]);
            }

            $invoice->refresh()->load('items')->recalculateTotal();
        }
    }

    private function createBooksData(User $user, Carbon $today): void
    {
        $business = BookBusiness::query()->create([
            'name' => 'Open Hands Demo',
            'legal_name' => 'Open Hands Demo Pty Ltd',
            'abn' => '12 345 678 901',
            'gst_registered' => true,
            'gst_rate' => 10,
            'bas_frequency' => 'quarterly',
            'active' => true,
        ]);

        $business->users()->attach($user->id, ['role' => 'owner']);
        $user->update(['default_book_business_id' => $business->id]);

        $fyStart = $today->month >= 7 ? $today->year : $today->year - 1;
        BookFinancialYear::query()->create([
            'book_business_id' => $business->id,
            'start_date' => Carbon::create($fyStart, 7, 1),
            'status' => 'open',
        ]);

        $account = BookBankAccount::query()->create([
            'book_business_id' => $business->id,
            'name' => 'Business Transaction Account',
            'institution' => 'Example Bank',
            'account_name' => 'Open Hands Demo',
            'last_four' => '4826',
            'active' => true,
        ]);

        $categories = [];
        foreach ([
            ['key' => 'sales', 'type' => 'income', 'name' => 'Web development income', 'gst' => 'gst_applicable'],
            ['key' => 'hosting', 'type' => 'income', 'name' => 'Hosting and domains', 'gst' => 'gst_applicable'],
            ['key' => 'software', 'type' => 'expense', 'name' => 'Software subscriptions', 'gst' => 'gst_applicable'],
            ['key' => 'cloud', 'type' => 'expense', 'name' => 'Cloud hosting', 'gst' => 'gst_applicable'],
            ['key' => 'office', 'type' => 'expense', 'name' => 'Office expenses', 'gst' => 'gst_applicable'],
            ['key' => 'insurance', 'type' => 'expense', 'name' => 'Business insurance', 'gst' => 'gst_free'],
            ['key' => 'fees', 'type' => 'expense', 'name' => 'Bank fees', 'gst' => 'gst_free'],
        ] as $order => $definition) {
            $categories[$definition['key']] = BookCategory::query()->create([
                'book_business_id' => $business->id,
                'type' => $definition['type'],
                'name' => $definition['name'],
                'report_order' => ($order + 1) * 10,
                'default_gst_treatment' => $definition['gst'],
                'default_business_use_percentage' => 100,
                'default_purchase_type' => 'non_capital',
                'active' => true,
            ]);
        }

        $transactions = [
            [-2, 2860.00, 'sales', 'Harbour Build Co. — monthly development'],
            [-5, -49.00, 'software', 'Source code repository subscription'],
            [-7, -132.00, 'cloud', 'Managed cloud servers'],
            [-9, 1746.25, 'sales', 'Riverstone Events — application improvements'],
            [-12, -38.50, 'office', 'Office supplies'],
            [-15, 792.00, 'hosting', 'Annual hosting and domain renewals'],
            [-18, -109.00, 'software', 'Development tools subscription'],
            [-21, 1237.50, 'sales', 'Northbank Architecture — client portal'],
            [-25, -24.00, 'fees', 'Business account fee'],
            [-29, -95.00, 'cloud', 'Backup and monitoring services'],
            [-34, 1980.00, 'sales', 'Summit Electrical — compliance workflow'],
            [-39, -286.00, 'insurance', 'Professional indemnity insurance'],
            [-46, 1485.00, 'sales', 'Wildfern Consulting — Laravel support'],
            [-54, -79.00, 'software', 'Email delivery service'],
            [-63, 2145.00, 'sales', 'Tasman Legal Services — document automation'],
            [-72, -165.00, 'cloud', 'Cloud infrastructure'],
            [-83, 528.00, 'hosting', 'Managed website services'],
            [-96, -64.90, 'office', 'Internet and communications'],
        ];

        foreach ($transactions as [$dayOffset, $amount, $category, $description]) {
            $gstApplicable = $categories[$category]->default_gst_treatment === 'gst_applicable';
            $absoluteAmount = abs($amount);
            $gst = $gstApplicable ? round($absoluteAmount / 11, 2) : 0;
            $net = $amount >= 0 ? $amount - $gst : $amount + $gst;

            BookTransaction::query()->create([
                'book_business_id' => $business->id,
                'book_bank_account_id' => $account->id,
                'book_category_id' => $categories[$category]->id,
                'transaction_date' => $today->copy()->addDays($dayOffset),
                'amount' => $amount,
                'business_use_percentage' => 100,
                'business_amount' => $amount,
                'net_amount' => $net,
                'gst_amount' => $gst,
                'gst_treatment' => $categories[$category]->default_gst_treatment,
                'sale_type' => $amount >= 0 ? 'taxable' : null,
                'purchase_type' => $amount < 0 ? 'non_capital' : null,
                'source' => 'manual',
                'payment_source' => 'business_bank',
                'description' => $description,
                'notes' => 'Fictional portfolio demonstration transaction.',
            ]);
        }
    }
}
