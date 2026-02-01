<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Hosting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceProjectSummary;
use App\Models\Project;
use App\Models\TimeEntry;
use App\Services\InvoiceBuilderService;
use Carbon\Carbon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\In;
use Livewire\Component;
use Filament\Schemas\Schema;

class Invoices extends Component implements HasSchemas, HasTable
{
    use InteractsWithSchemas;
    use InteractsWithTable;
    use InteractsWithActions;

    public $view;
    public $today;
    public $activeEntry;
    public $activeClient;
    public $activeInvoice;
    public $activeProject;
    public $activeSummary;
    public $activeSummaryText;
    public $currentDay;
    public $invoiceSummary;
    public $invoiceItems;

    // Add Entries
    public $stage = 1;
    public $clientText = 'Client';
    public $projectText = 'Project';
    public $projectID = null;
    public $dateText = 'Date';
    public $timeText = 'Time';
    public $activity = null;

    // Calendar (weeks of days)
    public array $calendarData = [];

    // Toggle: show full week vs Mon–Fri calendar
    public bool $showWeekend = true;

    public $livewireComponent;
    public ?Invoice $invoice = null;
    public ?string $previewJson = null;


    // Client / Projects
    public $clients;
    public $clientProjects;
    public $currentClient;
    public $currentProject;

    // Form state
    public ?array $itemData = [];
    public ?array $entryData = [];
    public ?array $createData = [];
    public ?array $deleteData = [];

    //public function mount()
    public function mount(Invoice $invoice = null)
    {
        $tz = 'Australia/Tasmania';
        $this->view = 'list';

        $this->today = Carbon::now()->timezone($tz)->startOfDay();
        $this->currentDay = Carbon::now()->timezone($tz)->startOfDay();

        $this->activeEntry = [];
        $this->deleteData = [];
        $this->livewireComponent = 'Invoices';

        $this->activeInvoice = Invoice::with(['items.project', 'items.timeEntry', 'projectSummaries',])->find($invoice->id);
        if ($this->activeInvoice) {
            $this->view = 'single';
        }

        // Initialise forms (important)
        $this->itemForm->fill();
        $this->entryForm->fill();
        $this->createForm->fill();

        $this->refeshInvoice();
    }

    public function render()
    {
        return view('livewire.invoices');
    }

    public function entryForm(Schema $schema): Schema
    {
        return $schema->components(TimeEntry::getEntryForm())->statePath('entryData')->model(TimeEntry::class);
    }

    public function createForm(Schema $schema): Schema
    {
        return $schema->components(Invoice::getCreateInvoiceForm())->statePath('createData')->model(Invoice::class);
    }

    public function itemForm(Schema $schema): Schema
    {
        return $schema->components(Invoice::getItemForm())->statePath('itemData')->model(InvoiceItem::class);
    }

    //
    // VIEW SWITCHING
    //
    public function changeView($view)
    {
        if ($view = 'list')
            return redirect('/invoice');

        return redirect('/invoice/' . $invoice->id);
    }

    public function refeshInvoice()
    {
        $this->invoiceSummary = TimeEntry::getEntrySummary();
        if ($this->activeInvoice) {
            $this->invoiceItems = $this->activeInvoice->getInvoiceItems(); //$this->getInvoiceItems();
            $this->activeInvoice->recalculateTotal();
            $this->activeInvoice->refresh();
        }
        //ray($this->invoiceItems);
    }

    public function openInvoice(Invoice $record)
    {
        $this->view = 'single';
        $this->activeInvoice = Invoice::with(['items.project', 'items.timeEntry', 'projectSummaries',])->find($record->id);

        // build summary for each project from TimeEntries
        $rows = $this->getInvoiceProjects();
    }

    public function printInvoice()
    {
        $url = route('invoice.print.view', ['invoice' => $this->activeInvoice->id]);

        // Dispatch browser event to open new tab
        $this->dispatch('open-print-window', url: $url);
    }

    public function downloadPdf()
    {
        $url = route('invoice.pdf', ['invoice' => $this->activeInvoice->id]);

        return redirect()->to($url);
    }


    //
    // MODALS
    //
    public function openCreateInvoiceModal()
    {
        $this->dispatch('open-modal', id: 'createInvoiceModal');
    }

    public function openCreateItemModal()
    {
        $data['client_id'] = (string)$this->activeInvoice->client->id;
        $data['type'] = 'custom';
        $this->itemForm->fill($data);
        $this->dispatch('open-modal', id: 'createItemModal');
    }

    public function openEditProjectSummaryModal($id)
    {
        $this->activeSummary = InvoiceProjectSummary::find($id);

        if ($this->activeSummary) {
            $this->activeProject = Project::find($this->activeSummary->project_id);

            if (trim($this->activeSummary->summary) !== '')
                $this->activeSummaryText = $this->activeSummary->summary;
            else {
                // Use auto generated
                $this->activeSummaryText = $this->activeInvoice->autoSummaryForProject($this->activeProject->id);
            }
            $this->dispatch('open-modal', id: 'editProjectSummaryModal');
        }
    }


    //
    // SAVING INVOICES
    //
    public function previewInvoice()
    {
        $data = $this->createForm->getState();

        ray('heer');
        if ($data['period'] == 'last_month') {
            $lastmonth = Carbon::now()->timezone('Australia/Tasmania')->subMonth();
            $data['period'] = 'month';
            $data['month'] = $lastmonth->format('n');
            $data['year'] = $lastmonth->format('Y');
        }

        $preview = app(InvoiceBuilderService::class)->preview($data);
        $this->previewJson = base64_encode(json_encode($preview));
        ray($data);
        ray($preview);

        $this->dispatch('open-modal', id: 'invoicePreviewModal');

        //return redirect()->route('invoice.preview', ['previewData' => base64_encode(json_encode($preview)),]);
    }

    public function createInvoice()
    {
        $data = $this->createForm->getState();

        if ($data['period'] == 'last_month') {
            $data['period'] = 'month';
            $data['month'] = Carbon::now()->timezone('Australia/Tasmania')->subMonth()->format('Y-m-d');
        } elseif ($data['period'] == 'month') {
            $month = Carbon::create($this->today->year, $data['month'], 1);

            // If future → push back 1 year
            if ($month->greaterThan($this->today))
                $month->subYear();

            $data['month'] = $month->format('Y-m-d');
        }

        $client = Client::with('projects')->findOrFail($data['client_id']);
        //$result = app(InvoiceBuilderService::class)->build($data, $client, saveInvoice: true);

        Notification::make()->success()->title("Invoice created")->send();

        //return redirect("/invoice/" . $result['invoice']->id);
    }

    public function saveProjectSummary(): void
    {
        if ($this->activeSummary) {
            $this->activeSummary->summary = $this->activeSummaryText;
            $this->activeSummary->save();
            $this->refeshInvoice();
        }
        $this->dispatch('close-modal', id: 'editProjectSummaryModal');
        Notification::make()->title('Summary updated')->success()->send();
    }

    public function markInvoiceSent(): void
    {
        if ($this->activeInvoice) {
            $this->activeInvoice->issue_date = $this->today;
            $this->activeInvoice->due_date = $this->today->copy()->addDays(7);
            $this->activeInvoice->status = 'sent';
            $this->activeInvoice->save();
            $this->activeInvoice->refresh();
        }
        Notification::make()->title('Invoice updated')->success()->send();
    }

    public function markInvoicePaid(): void
    {
        if ($this->activeInvoice) {
            $this->activeInvoice->paid_date = $this->today;
            $this->activeInvoice->status = 'paid';
            $this->activeInvoice->save();
            $this->activeInvoice->refresh();
        }
        Notification::make()->title('Invoice updated')->success()->send();
    }


    public function saveEntry(): void
    {
        $data = $this->entryForm->getState();
        unset($data['client_id']);

        // Create fake start/end times
        $data['activity'] = $data['activity'] ?? 'Support tickets';
        $data['rate'] = $this->activeEntry->project->rate ?? $this->activeEntry->client->rate;
        $data['start'] = Carbon::createFromFormat('Y-m-d H:i', $this->currentDay->format('Y-m-d') . ' 09:00');
        $data['end'] = Carbon::createFromFormat('Y-m-d H:i', $this->currentDay->format('Y-m-d') . ' 09:00')->addMinutes((int)$this->timeText * 60);

        $this->activeEntry->update($data);

        $this->dispatch('close-modal', id: 'editEntryModal');
        Notification::make()->success()->title('Entry updated')->color('success')->send();
    }

    public function createItem(): void
    {
        $data = $this->itemForm->getState();

        // Determin type
        if (isset($data['subtype'])) {
            $type_id = $data['subtype'];
            if ($data['type'] == 'domain') {
                $domain = Domain::find($type_id);
                $data['domain_id'] = $domain->id;
                $data['description'] = $domain->name;
                $data['summary'] = $domain->summary;
                $data['quantity'] = 1.00;
                $data['rate'] = $domain->rate;
            }
            if ($data['type'] == 'hosting') {
                $host = Hosting::find($type_id);
                $data['hosting_id'] = $host->id;
                $data['description'] = "Web Hosting for $host->name (1 year)";
                $data['summary'] = $host->summary;
                $data['quantity'] = 1.00;
                $data['rate'] = $host->rate;
            }
            if ($data['type'] == 'time') {
                $entry = TimeEntry::find($type_id);
                $rate = $entry->rate ?? $entry->project->rate ?? $client->rate;
                $rate = ($entry->entry_type != 'payback') ? $rate : $rate * -1;
                $hours = $entry->duration / 60;

                $data['time_entry_id'] = $entry->id;
                $data['description'] = $entry->project->name;
                $data['quantity'] = round($hours, 2);
                $data['rate'] = $rate;

                // Mark entry invoiced
                $entry->update(['invoice_id' => $this->activeInvoice->id]);

            }
        }
        unset($data['subtype']);
        unset($data['client_id']);

        $data['invoice_id'] = $this->activeInvoice->id;
        $item = InvoiceItem::create($data);

        $this->refeshInvoice();
        $this->dispatch('close-modal', id: 'createItemModal');
        Notification::make()->success()->title('Item created')->color('success')->send();
    }

    //
    // DELETE ENTRIES
    //
    public function confirmDeleteEntry($id)
    {
        $entry = TimeEntry::find($id);

        if ($entry) {
            $this->deleteData = ['id' => $id, 'name' => $entry->project->name . ' (' . $entry->DurationHours . 'h)',];
            $this->dispatch('open-modal', id: 'deleteEntryModal');
        }
    }

    public function deleteEntry($id): void
    {
        $entry = TimeEntry::find($id);
        if (!$entry) return;

        $entry->delete();
        $this->deleteData = [];

        $this->dispatch('close-modal', id: 'deleteEntryModal');
        $this->dispatch('close-modal', id: 'editEntryModal');

        Notification::make()->danger()->title('Entry deleted')->color('danger')->send();
    }

    public function confirmDeleteInvoice($id)
    {
        $invoice = Invoice::find($id);
        if ($invoice) {
            $this->deleteData = ['id' => $invoice->id, 'name' => $invoice->client->name];
            $this->dispatch('open-modal', id: 'deleteInvoiceModal');
        }
    }

    public function deleteInvoice($id)
    {
        $invoice = Invoice::with('items')->find($id);
        if (!$invoice) return;

        // Remove invoice_id from any TimeEntries previously associated with deleted Invoice
        DB::transaction(function () use ($invoice) {
            // Release time entries
            TimeEntry::whereIn('id', $invoice->items->where('type', 'time')->pluck('time_entry_id')->filter())->update(['invoice_id' => null]);
            $invoice->delete();
            $this->deleteData = [];
        });

        $this->dispatch('close-modal', id: 'deleteInvoiceModal');
        Notification::make()->danger()->title('Invoice deleted')->color('danger')->send();
        return redirect('/invoice');
    }

    //
    // DELETE INVOICE ITEMS
    //
    public function confirmDeleteItem($id)
    {
        $item = InvoiceItem::find($id);

        if ($item) {
            if ($item->type == 'time') $name = $item->description . " ($item->quantity)";
            if ($item->type == 'hosting') $name = "Web hosting " . $item->hosting->domain;
            if ($item->type == 'domain') $name = "Domain " . $item->domain->name;
            if ($item->type == 'custom') $name = $item->description;

            $this->deleteData = ['id' => $id, 'name' => $name,];
            $this->dispatch('open-modal', id: 'deleteItemModal');
        }
    }

    public function deleteItem($id)
    {
        $item = InvoiceItem::find($id);
        if (!$item) return;

        $item->delete();
        $this->deleteData = [];

        // If Deleted last item, also delete invoice
        if ($this->activeInvoice->items->count() == 0) {
            $this->activeInvoice->delete();
            return redirect('/invoice');
        }


        $this->refeshInvoice();
        $this->dispatch('close-modal', id: 'deleteItemModal');
        Notification::make()->danger()->title('Item deleted')->color('danger')->send();
    }

    //
    //  PROJECT SUMMARY
    //
    /**
     * Build line rows for the invoice table, with bullet lists under each project.
     *
     * Each row:
     * [
     *   'project_id'  => int,
     *   'description' => string,  // project name
     *   'rate'        => float,
     *   'qty'         => float,   // total hours
     *   'total'       => float,   // money
     *   'bullets'     => string[] // list of lines from summary
     * ]
     */
    public function getInvoiceProjects(): array
    {
        if (!$this->activeInvoice)
            return [];

        $rows = [];

        // Projects (TimeEntries)
        foreach ($this->activeInvoice->itemsGroupedByProject() as $projectId => $group) {
            // Get actual project summary record or create blank one
            $summaryRecord = $this->activeInvoice->getOrCreateProjectSummary($projectId);

            // Get details of project summary but if blank generate possible one from TimeEntries
            $summary = $this->activeInvoice->summaryForProject($projectId);

            $bullets = collect(preg_split('/\r\n|\r|\n/', trim((string)$summary)))->filter()->values()->toArray();
            $items = $group['items'];

            $rows[] = [
                'project_id' => $projectId,
                'project_name' => $group['project_name'],
                'rate' => $items->first()->rate,
                'qty' => $items->sum('quantity'),
                'total' => $items->sum(fn($i) => $i->quantity * $i->rate),
                'summary' => $summary,
                'summary_bullets' => $bullets,
                'summary_id' => $summaryRecord->id, // ID of invoice_project_summaries
                'items' => $items,
            ];
        }

        return $rows;
    }

//
// TABLE (Filament list of invoices)
//
    public function table(Table $table): Table
    {
        return $table
            ->query(Invoice::query())
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('client.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('issue_date')
                    ->label('Date')
                    ->date(format: 'Y M j')
                    ->sortable(),
                TextColumn::make('paid_date')
                    ->label('Paid')
                    ->date(),
                TextColumn::make('total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid'    => 'success',
                        'sent'    => 'primary',
                        'draft'   => 'gray',
                        'overdue' => 'danger',
                        default   => 'secondary',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('issue_date', 'desc')
            ->recordUrl(fn($record) => url('/invoice/' . $record->id))
            ->filters([
                //
            ])
            ->recordActions([
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

