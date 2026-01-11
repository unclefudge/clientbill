<?php

namespace App\Models;

use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = ['client_id', 'issue_date', 'due_date', 'paid_date', 'status', 'subtotal', 'gst', 'total', 'notes',];
    protected $casts = ['issue_date' => 'date', 'issue_date' => 'date', 'due_date' => 'date', 'paid_date' => 'date',];


    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function projectSummaries(): HasMany
    {
        return $this->hasMany(InvoiceProjectSummary::class);
    }

    public function billableItems()
    {
        return $this->items->filter(fn ($i) => $i->isBillable());
    }

    /**
     * Create missing summary records for all projects on this invoice.
     */
    public function generateMissingSummaries(): void
    {
        $projectIds = $this->items->pluck('timeEntry.project_id')->unique();
        foreach ($projectIds as $projectId) {
            if (!$projectId) continue;

            InvoiceProjectSummary::firstOrCreate(['invoice_id' => $this->id, 'project_id' => $projectId,]);
        }
    }

    public function getInvoiceItems(): array
    {
        if (!$this->items->count())
            return [];

        $items = ['hosting' => [], 'domains' => [], 'projects' => [],];

        // Hosting
        $items['hosting'] = $this->items->where('type', 'hosting')->values()->all();

        // Domains
        $domains = $this->items->where('type', 'domain');

        if ($domains->isEmpty())
            $items['domains'] = [];
        else {
            $domainNames = $domains->map(fn($i) => $i->domain->name)->all();
            $rates = $domains->pluck('rate')->map(fn($r) => (int)$r);
            // Determine how many years these domain renew for
            $renewals = $domains->map(fn($i) => (int)($i->domain->renewal ?? 1));  // default 1 year if missing
            $renewalMin = $renewals->min();
            $renewalMax = $renewals->max();
            $description = ($renewalMin == $renewalMax) ? "Domain registration/renewal ($renewalMin years)" : "Domain registration/renewal ($renewalMin-$renewalMax years)";

            $items['domains'] = [
                'description' => $description,
                'quantity' => $domains->count(),
                'total' => $domains->sum('rate'),
                'rateMin' => $rates->min(),
                'rateMax' => $rates->max(),
                'summary' => implode("\n", $domainNames),
                'items' => $domains,
            ];
        }

        // Projects
        foreach ($this->itemsGroupedByProject() as $projectId => $group) {
            // Get actual project summary record or create blank one
            $summaryRecord = $this->getOrCreateProjectSummary($projectId);

            // Get details of project summary but if blank generate possible one from TimeEntries
            $summary = $this->summaryForProject($projectId);

            $bullets = collect(preg_split('/\r\n|\r|\n/', trim((string)$summary)))->filter()->values()->toArray();
            $entryitems = $group['items'];
            $billableItems = $entryitems->where('type', 'time')->filter(fn ($i) => $i->isBillable());

            $items['projects'][] = [
                'project_id' => $projectId,
                'project_name' => $group['project_name'],
                'rate' => $entryitems->first()->rate,
                //'qty' => $entryitems->reduce(fn($carry, $i) => $carry + ($i->rate > 0 ? $i->quantity : -$i->quantity), 0),  // only sums items with postive rate $entryitems->sum('quantity'),
                //'total' => $entryitems->sum(fn($i) => $i->quantity * $i->rate),
                'qty'   => $billableItems->sum('quantity'),
                'total' => $billableItems->sum(fn ($i) => $i->quantity * $i->rate),
                'summary' => $summary,
                'summary_bullets' => $bullets,
                'summary_id' => $summaryRecord->id, // ID of invoice_project_summaries
                'items' => $entryitems,
            ];
        }

        // Custome
        $items['custom'] = $this->items->where('type', 'custom')->values()->all();

        return $items;
    }

    /**
     * Group invoice items by project.
     *
     * Collection keyed by project_id each value: ['project_id', 'project_name', 'items' => Collection]
     */
    public function itemsGroupedByProject()
    {
        return $this->items->filter->project
            ->groupBy(fn($item) => $item->project->id)
            ->map(function ($group, $projectId) {
                return [
                    'project_id' => $projectId,
                    'project_name' => $group->first()->project->name,
                    'items' => $group,
                ];
            });
    }

    //
    // Get or create the summary record for a project.
    //
    public function getOrCreateProjectSummary($projectId): InvoiceProjectSummary
    {
        return $this->projectSummaries()->firstOrCreate(
            ['project_id' => $projectId],
            ['summary' => ''] // start blank;
        );
    }

    //
    // Use saved summary if present, otherwise auto-generate.
    //
    public function summaryForProject(int $projectId): string
    {
        $saved = $this->projectSummaries()->where('project_id', $projectId)->first();

        if ($saved && trim($saved->summary) !== '')
            return $saved->summary;

        return $this->autoSummaryForProject($projectId);
    }

    //
    // Auto-generate a summary for a given project based on time-entry activities.
    //
    public function autoSummaryForProject(int $projectId): string
    {
        $activities = $this->items->filter->project
            ->filter(fn($item) => $item->project->id === $projectId)
            ->map(fn($item) => $item->timeEntry?->activity)
            ->filter()->unique()->values();

        if ($activities->isEmpty()) {
            return 'Work completed on project.';
        }

        return $activities->map(fn($a) => "$a")->join("\n");
    }

    //
    // Convert all unbilled time entries into invoice items.
    //
    public function addItemsFromTimeEntries(): void
    {
        $entries = $this->client->projects()->with('timeEntries')->get()->pluck('timeEntries')
            ->flatten()->where('billable', true)->whereNull('invoice_id')->whereIn('entry_type', ['regular', 'prebill']);

        foreach ($entries as $entry) {
            $hours = $entry->duration / 60;
            $rate = $entry->project->rate ?? $this->client->rate;

            InvoiceItem::create([
                'invoice_id' => $this->id,
                'time_entry_id' => $entry->id,
                'type' => 'time',
                'description' => $entry->notes ?: $entry->project->name,
                'summary' => null,
                'quantity' => round($hours, 2),
                'rate' => $rate,
            ]);

            $entry->markAsBilled($this->id);
        }

        $this->recalculateTotal();
    }

    //
    // Recalculate subtotal, GST and total.
    //
    public function recalculateTotal(): void
    {
        //$subtotal = $this->items()->sum('amount');
        $subtotal = $this->items->filter(fn ($i) => $i->isBillable())->sum('amount');
        $gst = round($subtotal * 0.10, 2);
        $total = $subtotal + $gst;

        $this->subtotal = $subtotal;
        $this->gst = $gst;
        $this->total = $total;

        $this->saveQuietly();
    }

    //
    // Auto-update status when appropriate.
    //
    public function updateStatusAutomatically(): void
    {
        if ($this->paid_date && $this->status !== 'paid')
            $this->status = 'paid';

        $this->saveQuietly();
    }

    public function getInvoiceBadgeAttribute()
    {
        // 'draft', 'sent', 'paid', 'overdue'
        if ($this->entry_type == 'draft') // gray
            return '<div class="inline-flex items-center px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-300
            bg-gray-500/10 dark:bg-gray-400/10 border border-gray-500/20 dark:border-gray-400/20 rounded-full">Draft</div>';

        if ($this->entry_type == 'sent') // warning/amber
            return '<div class="inline-flex items-center px-3 py-1 text-xs font-medium text-yellow-600 dark:text-yellow-300
            bg-yellow-500/10 dark:bg-yellow-400/10 border border-yellow-500/20 dark:border-yellow-400/20 rounded-full">Sent</div>';

        if ($this->status == 'paid') // green
            return '<div class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-600 dark:text-green-300
            bg-green-500/10 dark:bg-green-400/10 border border-green-500/20 dark:border-green-400/20 rounded-full">Paid</div>';

        if ($this->entry_type == 'overdue') // red
            return '<div class="inline-flex items-center px-3 py-1 text-xs font-medium text-red-500 dark:text-red-300
            bg-red-500/10 dark:bg-red-400/10 border border-red-500/20 dark:border-red-400/20 rounded-full">Overdue</div>';

    }

    protected static function booted()
    {
        static::saving(function (Invoice $invoice) {
            $invoice->updateStatusAutomatically();
        });

        static::saved(function (Invoice $invoice) {
            $invoice->recalculateTotal();
        });

        static::deleting(function (Invoice $invoice) {
            $invoice->recalculateTotal();
        });
    }

    //
    // Filament Forms
    //
    public static function getCreateInvoiceForm(): array
    {
        return [
            Grid::make()
                ->columns(1)
                ->schema([

                    // ======================================================
                    //  CLIENT SELECTOR
                    // ======================================================
                    Select::make('client_id')
                        ->columnSpanFull()
                        ->label('Client')
                        ->required()
                        ->options(Client::where('active', true)->orderBy('order')->pluck('name', 'id'))
                        ->default(1)
                        ->selectablePlaceholder(false)
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->native(false)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state == 1)
                                $set('period', 'last_month');
                            else
                                $set('period', 'unbilled');
                        }),

                    // ======================================================
                    //  PERIOD TYPE SELECTOR
                    // ======================================================
                    Select::make('period')
                        ->columnSpanFull()
                        ->label('Billing Period')
                        ->required()
                        ->options([
                            //'last_invoice' => 'Since Last Invoice → Today',
                            'unbilled' => 'All unbilled',
                            'last_month' => 'Last Month',
                            'month' => 'Specific Month',
                            'range' => 'Custom Date Range',
                        ])
                        ->default('last_month')
                        ->selectablePlaceholder(false)
                        ->reactive()
                        ->native(false),

                    // ======================================================
                    //  MONTH SELECTOR
                    // ======================================================
                    // MONTH PICKER
                    Grid::make(2)
                        ->visible(fn($get) => $get('period') === 'month')
                        ->schema([
                            Select::make('month')
                                ->options([
                                    1=>'Jan',2=>'Feb',3=>'Mar',4=>'Apr',
                                    5=>'May',6=>'Jun',7=>'Jul',8=>'Aug',
                                    9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dec',
                                ])
                                ->required(fn(Get $get) => $get('period') === 'month')
                                ->selectablePlaceholder(false)
                                ->default(fn() => Carbon::now()->format('n'))
                                ->native(false),

                            Select::make('year')
                                ->options(collect(range(now()->year-5, now()->year+1))->mapWithKeys(fn($y)=>[$y=>$y]))
                                ->required(fn(Get $get) => $get('period') === 'month')
                                ->selectablePlaceholder(false)
                                ->default(fn() => Carbon::now()->format('Y'))
                                ->native(false),
                        ]),

                    /*Select::make('month')
                        ->columnSpanFull()
                        ->label('Invoice Month')
                        ->options(['01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'])
                        ->default(function () {
                            return Carbon::now()->subMonth()->format('m');
                        })
                        ->selectablePlaceholder(false)
                        ->native(false)
                        ->visible(fn(Get $get) => $get('period') === 'month')
                        ->required(fn(Get $get) => $get('period') === 'month'),*/

                    // ======================================================
                    //  DATE RANGE SELECTOR
                    // ======================================================
                    Grid::make(2)
                        ->schema([
                            DatePicker::make('range_start')
                                ->label('Start Date')
                                ->native(false)
                                ->closeOnDateSelection()
                                ->visible(fn(Get $get) => $get('period') === 'range')
                                ->required(fn(Get $get) => $get('period') === 'range'),

                            DatePicker::make('range_end')
                                ->label('End Date')
                                ->native(false)
                                ->closeOnDateSelection()
                                ->visible(fn(Get $get) => $get('period') === 'range')
                                ->required(fn(Get $get) => $get('period') === 'range'),
                        ]),

                ]),

            // ==========================================================
            //  FORM ACTION BUTTONS (PREVIEW + CREATE)
            // ==========================================================
            /*Actions::make([
                Action::make('preview')
                    ->label('Preview Invoice')
                    ->color('gray')
                    ->icon('heroicon-o-eye')
                    ->action(function () {
                        $this->previewInvoice();
                    }),

                Action::make('createInvoice')
                    ->label('Create Invoice')
                    ->color('primary')
                    ->icon('heroicon-o-document-plus')
                    ->action(function () {
                        $this->createInvoice();
                    }),
            ])
                ->fullWidth(),*/
        ];

    }

    public static function getCreateInvoice2Form(): array
    {
        return [
            Grid::make()
                ->columns(['sm' => 1, 'md' => 12, 'lg' => 12])
                ->schema([
                    /*
                    |--------------------------------------------------------------------------
                    | CLIENT SELECT
                    |--------------------------------------------------------------------------
                    */
                    Select::make('client_id')
                        ->columnSpanFull()
                        ->label('Client')
                        ->required()
                        ->options(Client::where('active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                        ->default('1')
                        ->live()
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state == 1)
                                $set('period', Carbon::now()->subMonth()->format('m'));
                            else
                                $set('period', 'last');
                        }),

                    /*
                    |--------------------------------------------------------------------------
                    | PERIOD SELECT
                    |--------------------------------------------------------------------------
                    */
                    Select::make('period')
                        ->columnSpanFull()
                        ->label('Period')
                        ->required()
                        ->options(['last' => 'Since last invoice', '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April', '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August', '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'])
                        ->default(function () {
                            return Carbon::now()->subMonth()->format('m');
                        })
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->live()
                        ->preload(),
                ]),
        ];
    }

    public static function getItemForm(): array
    {
        return [
            Grid::make()
                ->columns(['sm' => 1, 'md' => 12, 'lg' => 12])
                ->schema([
                    /*
                    |--------------------------------------------------------------------------
                    | TYPE
                    |--------------------------------------------------------------------------
                    */
                    Select::make('type')
                        ->columnSpanFull()
                        ->label('Type')
                        ->required()
                        ->options(['custom' => 'Custom', 'domain' => 'Domain', 'hosting' => 'Hosting', 'time' => 'Time'])
                        ->default('custom')
                        ->live()
                        ->native(false)
                        ->selectablePlaceholder(false),


                    /*
                    |--------------------------------------------------------------------------
                    | SUB-TYPE (Hosting, Domain, Time, Product)
                    |--------------------------------------------------------------------------
                    */
                    Select::make('subtype')
                        ->columnSpanFull()
                        ->label('Sub type')
                        ->required()
                        ->options(function (Get $get) {
                            $type = $get('type');
                            $clientId = $get('client_id');

                            return match ($type) {
                                // DOMAIN OPTIONS
                                'domain' => Domain::where('client_id', $clientId)->orderBy('name')->pluck('name', 'id')->toArray(),

                                // HOSTING OPTIONS
                                'hosting' => Hosting::where('client_id', $clientId)->orderBy('name')->pluck('name', 'id')->toArray(),

                                // TIME ENTRY OPTIONS
                                'time' => TimeEntry::query()->whereNull('invoice_id') // only unbilled entries
                                ->whereHas('project', fn($q) => $q->where('client_id', $clientId))
                                    ->with('project')->orderBy('date')->get()
                                    ->mapWithKeys(function ($entry) {
                                        $hours = number_format($entry->duration / 60, 2);
                                        $label = $entry->date->format('d/m/Y') .
                                            " – {$hours}h – {$entry->activity}";
                                        return [$entry->id => $label];
                                    })
                                    ->toArray(),

                                default => [],
                            };
                        })
                        ->default('custom')
                        ->live()
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->visible(fn(Get $get) => in_array($get('type'), ['domain', 'hosting', 'time'])),


                    /*
                    |--------------------------------------------------------------------------
                    | DESCRIPTION
                    |--------------------------------------------------------------------------
                    */
                    TextInput::make('description')
                        ->columnSpanFull()
                        ->label('Description')
                        ->placeholder('What did you do to make the bacon...')
                        ->required()
                        ->visible(fn(Get $get) => !in_array($get('type'), ['domain', 'hosting', 'time'])),

                    /*
                    |--------------------------------------------------------------------------
                    | RATE (cost)
                    |--------------------------------------------------------------------------
                    */
                    TextInput::make('rate')
                        ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                        ->label('Cost')
                        ->placeholder('Cost')
                        ->rules('numeric')
                        ->required()
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $qty = $get('quantity');
                            if ($qty)
                                $set('amount', $state * $qty);
                        })
                        ->visible(fn(Get $get) => !in_array($get('type'), ['domain', 'hosting', 'time'])),

                    /*
                    |--------------------------------------------------------------------------
                    | QUANTITY
                    |--------------------------------------------------------------------------
                    */
                    TextInput::make('quantity')
                        ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                        ->label('Quantity')
                        ->placeholder('Quantity')
                        ->default('1')
                        ->rules('numeric')
                        ->live(onBlur: true)
                        ->required()
                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            $rate = $get('rate');
                            if ($rate)
                                $set('amount', $state * $rate);
                        })->visible(fn(Get $get) => !in_array($get('type'), ['domain', 'hosting', 'time'])),

                    /*
                    |--------------------------------------------------------------------------
                    | AMOUNT
                    |--------------------------------------------------------------------------
                    */
                    TextInput::make('amount')
                        ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                        ->label('Amount')
                        ->placeholder('Amount')
                        ->disabled()
                        ->required()
                        ->visible(fn(Get $get) => !in_array($get('type'), ['domain', 'hosting', 'time'])),

                    /*
                    |--------------------------------------------------------------------------
                    | SUMMARY
                    |--------------------------------------------------------------------------
                    */
                    Textarea::make('summary')
                        ->columnSpanFull()
                        ->label('Summary')
                        ->placeholder('Optional summary...')
                        ->visible(fn(Get $get) => !in_array($get('type'), ['domain', 'hosting', 'time'])),


                    TextInput::make('client_id')
                        ->visible(false),

                ]),
        ];
    }
}
