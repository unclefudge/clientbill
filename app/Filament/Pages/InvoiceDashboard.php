<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Hosting;
use App\Models\Invoice;
use App\Models\TimeEntry;
use App\Services\InvoiceBuilderService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Services\SuggestedInvoiceService;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use BackedEnum;

class InvoiceDashboard extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
    protected static ?string $navigationLabel = 'Home';
    protected static ?string $slug = 'home';
    protected static ?int $navigationSort = 1;
    protected static bool $shouldRegisterNavigation = true;

    protected string $view = 'filament.pages.invoice-dashboard';

    public int $upcomingDays = 30;
    public int $hoursThisMonth = 0;
    public int $unbilledHours = 0;
    public int $renewalsCount = 0;
    public int $unpaidCount = 0;
    public int $overdueCount = 0;
    public int $alerts = 0;
    public array $suggestions = [];
    public array $modalEntries = [];
    public array $modalRenewals = ['domains' => [], 'hosting' => [],];
    public ?string $modalClientName = null;
    public ?int $modalClientId = null;

    public ?string $previewJson = null;

    // Form state
    public ?array $createData = [];

    public function mount(): void
    {
        // ---------------------------------------------
        // HOURS THIS MONTH
        // ---------------------------------------------
        $this->hoursThisMonth = TimeEntry::whereMonth('date', now()->month)->whereYear('date', now()->year)->sum('duration') / 60;

        // ---------------------------------------------
        // UNBILLED HOURS
        // ---------------------------------------------
        $this->unbilledHours = (int) round(TimeEntry::whereNull('invoice_id')->sum('duration') / 60, 1);

        // ---------------------------------------------
        // UPCOMING RENEWALS WITHIN N DAYS (domains + hosting)
        // ---------------------------------------------
        $this->renewalsCount = Domain::renewingWithinDays($this->upcomingDays)->count() + Hosting::renewingWithinDays($this->upcomingDays)->count();

        // ---------------------------------------------
        // CLIENT ALERT COUNT (same logic as suggestions)
        // ---------------------------------------------
        $service = app(SuggestedInvoiceService::class);
        $this->suggestions = $service->build($this->upcomingDays);
        ray($this->suggestions);

        // Count number of clients with actionable issues
        $this->alerts = collect($this->suggestions)
            ->filter(fn ($c) =>
                $c['unbilled_hours'] > 0 ||
                $c['renewals'] > 0 ||
                $c['hour_balance'] != 0 ||
                $c['can_invoice']
            )
            ->count();

        // ---------------------------------------------
        // UNPAID INVOICES
        // ---------------------------------------------
        $invoices = Invoice::where('status', 'sent')->get();

        $this->unpaidCount = $invoices->count();
        $this->overdueCount = $invoices->filter(fn ($i) => $i->due_date->isPast())->count();

        // Initialise forms (important)
        $this->createForm->fill();

    }

    public function createForm(Schema $schema): Schema
    {
        return $schema->components(Invoice::getCreateInvoiceForm())->statePath('createData')->model(Invoice::class);
    }

    //
    // Modals
    //
    public function openCreateInvoiceModal($client_id)
    {
        $this->modalClientId = $client_id;
        $data = ['client_id' => $client_id];
        $data['period'] = ($client_id == 1) ? 'last_month' : 'unbilled';
        $this->createForm->fill($data);
        $this->dispatch('open-modal', id: 'createInvoiceModal');
    }

    public function viewUnbilledHours($clientId = null)
    {
        // 1) Set modal title
        if ($clientId) {
            $client = Client::find($clientId);
            $this->modalClientName = $client->name;
        } else
            $this->modalClientName = "All Clients";

        // 2) Build base query
        $query = TimeEntry::whereNull('invoice_id')->with(['project', 'project.client'])->orderBy('date', 'desc');

        // 3) Filter to client if required
        if ($clientId)
            $query->whereHas('project', fn($q) => $q->where('client_id', $clientId));

        $entries = $query->get();

        // 4) Group + map into modal format
        $this->modalEntries = $entries->groupBy(fn($e) => $e->project->name ?? 'Unknown Project')
            ->map(function ($group) {
                return [
                    'project_name' => $group->first()->project->name ?? 'Unknown Project',
                    'total_minutes'  => $group->sum('duration'),
                    'total_hours'  => $group->sum('duration') / 60,
                    'entries'      => $group->map(fn($e) => [
                        'date'      => $e->date,
                        'duration'  => $e->duration,
                        'type'      => $e->entry_type,
                        'badge'     => $e->EntryTypeBadge,
                        'activity'  => $e->activity,
                    ])->values()->toArray(),
                ];
            })
            ->values()->toArray();

        $this->dispatch('open-modal', id: 'unbilledHoursModal');
    }

    public function viewRenewals($clientId = null)
    {
        // 1) Set modal header title
        if ($clientId) {
            $client = Client::find($clientId);
            $this->modalClientName = $client->name;
        } else
            $this->modalClientName = "All Clients";

        // 2) Domains query
        $domainQuery = Domain::renewingWithinDays($this->upcomingDays)->with('client');
        if ($clientId)
            $domainQuery->where('client_id', $clientId);

        $domains = $domainQuery->get();

        // 3) Hosting query
        $hostingQuery = Hosting::renewingWithinDays($this->upcomingDays)->with('client');
        if ($clientId)
            $hostingQuery->where('client_id', $clientId);

        $hosting = $hostingQuery->get();

        // 4) Package modal data
        $this->modalRenewals = ['domains' => $domains->toArray(), 'hosting' => $hosting->toArray(),];

        // 5) Open modal
        $this->dispatch('open-modal', id: 'renewalsModal');
    }



    //
    // SAVING INVOICES
    //
    public function previewInvoice()
    {
        $data = $this->createForm->getState();

        if ($data['period'] == 'last_month') {
            $lastmonth = Carbon::now()->timezone('Australia/Tasmania')->subMonth();
            $data['period'] = 'month';
            $data['month'] = $lastmonth->format('n');
            $data['year'] = $lastmonth->format('Y');
        }

        $preview = app(InvoiceBuilderService::class)->preview($data);
        $this->previewJson = base64_encode(json_encode($preview));

        $this->dispatch('open-modal', id: 'invoicePreviewModal');
    }

    public function createInvoice()
    {
        $data = $this->createForm->getState();
        //ray($data);

        if ($data['period'] == 'last_month') {
            $lastmonth = Carbon::now()->timezone('Australia/Tasmania')->subMonth();
            $data['period'] = 'month';
            $data['month'] = $lastmonth->format('n');
            $data['year'] = $lastmonth->format('Y');
        }

        $invoice = app(InvoiceBuilderService::class)->create($data);

        $this->dispatch('close-modal', id: 'invoicePreviewModal');
        $this->dispatch('close-modal', id: 'createInvoiceModal');
        Notification::make()->success()->title("Invoice created")->send();

        return redirect("/invoice/" . $invoice->id);
    }

}
