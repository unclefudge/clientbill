<?php

namespace App\Models;

use Filament\Actions\SelectAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use Wallo\FilamentSelectify\Components\ToggleButton;

class TimeEntry extends Model
{
    protected $fillable = ['project_id', 'activity', 'date', 'start', 'end', 'duration', 'rate', 'billable', 'entry_type', 'invoice_id', 'notes', 'import'];
    protected $casts = ['date' => 'date', 'billable' => 'boolean', 'hours' => 'decimal:2', 'entry_type' => 'string',];

    const TYPE_REGULAR = 'regular';
    const TYPE_PREBILL = 'prebill';
    const TYPE_PAYBACK = 'payback';

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function client()
    {
        return $this->hasOneThrough(Client::class, Project::class,
            'id',          // Foreign key on projects
            'id',          // Foreign key on clients
            'project_id',  // FK on time_entries
            'client_id'    // FK on projects
        );
    }

    public function scopeUnbilled($query)
    {
        // PAYBACK entries should never appear on invoice
        return $query->whereNull('invoice_id')->where('entry_type', '!=', self::TYPE_PAYBACK);
    }

    public function scopeBillableOnly($query)
    {
        return $query->where('billable', true);
    }

    public function shouldAppearOnInvoice(): bool
    {
        return in_array($this->entry_type, [self::TYPE_REGULAR, self::TYPE_PREBILL,]);
    }

    public function markAsBilled(int $invoiceId): void
    {
        $this->invoice_id = $invoiceId;
        $this->billable = true;
        $this->saveQuietly();
    }

    static public function getEntrySummary($month = null): array
    {
        // --------------------------------------------------
        // 1) Define date range
        // --------------------------------------------------
        if ($month) {
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
        } else {
            $start = Carbon::parse('2000-01-01');
            $end   = Carbon::today()->timezone('Australia/Tasmania');
        }

        // --------------------------------------------------
        // 2) Load entries for the DATE RANGE (slim query)
        //    Always eager-load project + client
        // --------------------------------------------------
        $entries = TimeEntry::with(['project.client'])->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get();

        // Gather client IDs present in this period
        $clientIds = $entries->pluck('project.client_id')->unique()->values();

        // --------------------------------------------------
        // 3) Preload ALL clients with projects + entries
        //    This lets us compute hour balances ONCE per client.
        // --------------------------------------------------
        $clientBalances = Client::whereIn('id', $clientIds)
            ->with(['projects.timeEntries:id,project_id,entry_type,duration'])
            ->get()
            ->mapWithKeys(function ($client) {

                $prebill = 0;
                $payback = 0;

                foreach ($client->projects as $project) {
                    foreach ($project->timeEntries as $entry) {
                        if ($entry->entry_type === 'prebill') {
                            $prebill += $entry->duration;
                        } elseif ($entry->entry_type === 'payback') {
                            $payback += $entry->duration;
                        }
                    }
                }

                $hours = ($prebill - $payback) / 60;

                // APPLY CLIENT ADJUSTMENT (in hours)
                $hours += $client->hourBalanceAdjustment;

                return [
                    $client->id => [
                        'balance' => $hours,
                        'label'   => $hours > 0
                            ? "{$hours} hours owed to client (work not yet delivered)"
                            : ($hours < 0
                                ? abs($hours) . " hours extra delivered (client owes)"
                                : "Balanced"),
                    ]
                ];
            });

        // --------------------------------------------------
        // 4) Build summary
        // --------------------------------------------------
        $summary = [];

        foreach ($entries as $entry) {
            $project = $entry->project;
            $client  = $project?->client;

            if (!$client || !$project) {
                continue;
            }

            $clientName  = $client->name;
            $projectName = $project->name;
            $hours       = $entry->duration / 60;

            // Make sure client data exists
            if (!isset($summary[$clientName])) {
                $summary[$clientName] = [
                    'projects'           => [],
                    'hours_total'        => 0,
                    'hours_unbilled'     => 0,
                    'entries'            => [],
                    'entries_invoiced'   => [],
                    'entries_unbilled'   => [],
                    'hour_balance'       => $clientBalances[$client->id]['balance'] ?? 0,
                    'hour_balance_label' => $clientBalances[$client->id]['label'] ?? 'Balanced',
                ];
            }

            // Ensure project bucket exists
            if (!isset($summary[$clientName]['projects'][$projectName])) {
                $summary[$clientName]['projects'][$projectName] = 0;
            }

            // Accumulate project hours
            $summary[$clientName]['projects'][$projectName] += $hours;
            $summary[$clientName]['hours_total'] += $hours;

            // Categorize entry
            $summary[$clientName]['entries'][] = $entry;

            if ($entry->invoice_id) {
                $summary[$clientName]['entries_invoiced'][] = $entry;
            } else {
                $summary[$clientName]['entries_unbilled'][] = $entry;
                $summary[$clientName]['hours_unbilled'] += $hours;
            }
        }

        // --------------------------------------------------
        // 5) Sort projects + clients alphabetically
        // --------------------------------------------------
        foreach ($summary as $clientName => &$clientData) {
            ksort($clientData['projects'], SORT_NATURAL | SORT_FLAG_CASE);
        }

        ksort($summary, SORT_NATURAL | SORT_FLAG_CASE);

        return $summary;
    }

    //
    // Getters / Setters
    //

    // helper to format hours for UI
    public function getDurationHoursAttribute(): float
    {
        return round($this->duration / 60, 2);
    }

    public function getTimeRangeAttribute()
    {
        $start = Carbon::parse($this->date->format('Y-m-d') . ' ' . $this->start);
        $end = $start->copy()->addMinutes($this->duration);

        return $start->format('g:i') . ' — ' . $end->format('g:i a');
    }

    public function getEntryTypeBadgeAttribute()
    {
        if ($this->entry_type == 'regular')
            return '<div class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-600 dark:text-green-300
            bg-green-500/10 dark:bg-green-400/10 border border-green-500/20 dark:border-green-400/20 rounded-full">Regular</div>';

        if ($this->entry_type == 'prebill')
            return '<div class="inline-flex items-center px-3 py-1 text-xs font-medium text-red-500 dark:text-red-300
            bg-red-500/10 dark:bg-red-400/10 border border-red-500/20 dark:border-red-400/20 rounded-full">Prebill</div>';

        if ($this->entry_type == 'payback')
            return '<div class="inline-flex items-center px-3 py-1 text-xs font-medium text-yellow-600 dark:text-yellow-300
            bg-yellow-500/10 dark:bg-yellow-400/10 border border-yellow-500/20 dark:border-yellow-400/20 rounded-full">Payback</div>';
    }

    public function getInvoicedBadgeAttribute()
    {
        if ($this->invoice_id)
            return '<div class="inline-flex items-center justify-center w-6 h-6 rounded-full border border-green-500/40 text-green-500 dark:border-green-400/40 dark:text-green-400
            bg-green-500/10 dark:bg-green-400/10"><x-heroicon-s-check class="w-3.5 h-3.5" /></div>';

        return '<div class="inline-flex items-center justify-center w-6 h-6 rounded-full border border-red-500/40 text-red-500 dark:border-red-400/40 dark:text-red-400
            bg-red-500/10 dark:bg-red-400/10"><x-heroicon-s-x-mark class="w-3.5 h-3.5" /></div>';
    }


    protected static function booted()
    {
        static::saving(function (TimeEntry $entry) {
            // If both start and end exist, compute duration
            // - not beig used as sometime I pre-bill for 24+ hours and it messes up th start/end time calcs
            /*if ($entry->start && $entry->end) {

                $start = Carbon::parse($entry->start);
                $end   = Carbon::parse($entry->end);

                // handle cases where end time is past midnight
                if ($end->lessThan($start)) {
                    $end->addDay();
                }

                $entry->duration = $start->diffInMinutes($end);
            }*/
        });
    }

    //
    // Filament Forms
    //
    public static function getEntryForm(): array
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
                        ->columnSpan(['sm' => 1, 'md' => 6, 'lg' => 6])
                        ->label('Client')
                        ->required()
                        ->options(Client::where('active', true)->orderBy('name')->pluck('name', 'id')->toArray())
                        ->live()
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->afterStateUpdated(fn(Set $set) => $set('project_id', null)),

                    /*
                    |--------------------------------------------------------------------------
                    | PROJECT SELECT (depends on client)
                    |--------------------------------------------------------------------------
                    */
                    Select::make('project_id')
                        ->columnSpan(['sm' => 1, 'md' => 6, 'lg' => 6])
                        ->label('Project')
                        ->required()
                        ->options(function (Get $get) {
                            $clientId = $get('client_id');

                            return Project::where('client_id', $clientId)->where('active', true)->orderBy('name')->pluck('name', 'id')->toArray();
                        })
                        ->default(function (Get $get) {
                            $clientId = $get('client_id');
                            if (!$clientId)
                                return null;

                            return Project::where('client_id', $clientId)->where('active', true)->orderBy('order')->first()->id;
                        })
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->live()
                        ->preload(),


                    /*
                    |--------------------------------------------------------------------------
                    | DURATION SELECT (0.5 → 10.0 in 0.5 increments)
                    |--------------------------------------------------------------------------
                    */
                    Select::make('duration')
                        ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                        ->label('Duration (hrs)')
                        ->placeholder('Select duration')
                        ->required()
                        ->options(function () {
                            $options = [];

                            for ($minutes = 30; $minutes <= 600; $minutes += 30) {   // 0.5h → 10h
                                $hours = $minutes / 60;

                                // Format: 1h, 1.5h, 2h, etc.
                                $label = fmod($hours, 1) === 0.0 ? intval($hours) . 'h' : $hours . 'h';

                                $options[$minutes] = $label;
                            }

                            return $options;
                        })
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->preload(),
                    /*
                    |--------------------------------------------------------------------------
                    | ENTRY TYPE
                    |--------------------------------------------------------------------------
                    */
                    Select::make('entry_type')
                        ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                        ->label('Entry Type')
                        ->placeholder('Select duration')
                        ->required()
                        ->options(['regular' => 'Regular', 'prebill' => 'Prebill / Claim', 'payback' => 'Payback / Delay'])
                        ->native(false)
                        ->selectablePlaceholder(false)
                        ->preload(),

                    /*
                    |--------------------------------------------------------------------------
                    | DATE
                    |--------------------------------------------------------------------------
                    */
                    DatePicker::make('date')
                        ->columnSpan(['sm' => 1, 'md' => 4, 'lg' => 4])
                        ->native(false)
                        ->required()
                        ->columnSpanFull(),


                    /*
                    |--------------------------------------------------------------------------
                    | ACTIVITY
                    |--------------------------------------------------------------------------
                    */
                    TextInput::make('activity')
                        ->columnSpanFull()
                        ->label('Activity')
                        ->placeholder('What did you do to make the bacon...')
                        ->columnSpanFull(),


                ]),
        ];
    }

}
