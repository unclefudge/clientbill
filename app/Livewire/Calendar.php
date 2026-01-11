<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Hosting;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\TimeEntry;
use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Filament\Schemas\Schema;

class Calendar extends Component implements HasSchemas
{
    use InteractsWithSchemas;

    public $view;
    public $today;
    public $activeEntry;
    public $activeClient;
    public $currentDay;
    public $currentMonth;
    public $calEntries;
    public $calSummary;
    public $calHosting;
    public $calDomains;
    public $pendingEntries;
    public $calFirstDay;
    public $calLastDay;
    public $selectYear;

    // Add Entries
    public $stage = 1;
    public $clientText = 'Client';
    public $projectText = 'Project';
    public $projectID = null;
    public $dateText = 'Date';
    public $timeText = 'Time';
    public $timeType = 'regular';
    public $activity = null;

    // Confirm Entry Updates
    public array $pendingEditData = [];

    // Calendar (weeks of days)
    public array $calendarData = [];

    // Toggle: show full week vs Mon–Fri calendar
    public bool $showWeekend = true;

    public $livewireComponent;

    // Client / Projects
    public $clients;
    public $clientProjects;
    public $currentClient;
    public $currentProject;

    // Form state
    public ?array $entryData = [];
    public ?array $deleteData = [];

    public function mount()
    {
        $tz = 'Australia/Tasmania';
        $this->view = 'month';

        $this->today = Carbon::now()->timezone($tz)->startOfDay();
        $this->currentMonth = Carbon::now()->timezone($tz)->startOfDay();
        $this->currentDay = Carbon::now()->timezone($tz)->startOfDay();
        $this->selectYear = $this->currentMonth->format('Y');

        $this->activeEntry = [];
        $this->deleteData = [];
        $this->livewireComponent = 'Calendar';

        $this->clients = Client::where('active', 1)->orderBy('order')->get();
        $this->clientProjects = $this->getActiveClientsWithProjects();

        // Initialise forms
        $this->entryForm->fill();

        // Build Calender cells
        $this->refreshCalendar();
    }

    public function render()
    {
        return view('livewire.calendar');
    }

    public function entryForm(Schema $schema): Schema
    {
        return $schema->components(TimeEntry::getEntryForm())->statePath('entryData')->model(TimeEntry::class);
    }

    public function refreshCalendar(): void
    {
        $this->calEntries = $this->getEntriesForMonth();
        $this->calSummary = TimeEntry::getEntrySummary($this->currentMonth);
        $this->calHosting = Hosting::whereMonth('date', $this->currentMonth->month)->where('active', 1)->get();
        $this->calDomains = Domain::whereMonth('next_renewal', $this->currentMonth->month)->where('active', 1)->get();
        $this->buildCalendar();
        //ray($this->calEntries);
        //ray($this->calSummary);
        //ray($this->calHosting);
    }

    //
    // VIEW SWITCHING
    //
    public function changeView($view): void
    {
        $this->view = $view;
        $this->refreshCalendar();
    }

    public function changeMonth($date): void
    {
        if ($date === 'prev')
            $this->currentMonth = $this->currentMonth->subMonth();
        elseif ($date === 'next')
            $this->currentMonth = $this->currentMonth->addMonth();
        elseif ($date === 'today')
            $this->currentMonth = Carbon::now()->timezone('Australia/Tasmania')->startOfDay();
        else
            $this->currentMonth = Carbon::parse($date)->startOfDay();

        $this->selectYear = $this->currentMonth->format('Y');
        $this->refreshCalendar();
    }

    public function changeYear($direction): void
    {
        $this->currentMonth = $direction === 'prev'
            ? $this->currentMonth->subYear()
            : $this->currentMonth->addYear();

        $this->selectYear = $this->currentMonth->year;

        $this->refreshCalendar();
    }

    public function changeStage($num)
    {
        $this->stage = $num;
    }

    public function setClient($name)
    {
        $this->clientText = $name;
        if (count($this->clientProjects[$name]) == 1) {
            $this->projectID = array_key_first($this->clientProjects[$name]);
            $this->projectText = $this->clientProjects[$name][$this->projectID];
            $this->stage = 3;
        } else
            $this->stage = 2;
    }

    public function setProject($id, $name)
    {
        $this->projectID = $id;
        $this->projectText = $name;
        $this->stage = 3;
    }

    public function setTime($num)
    {
        $this->timeText = $num;
        $this->stage = 4;
    }

    public function setType($type)
    {
        $this->timeType = $type;
    }


    //
    // MODALS
    //
    public function openAddEntryModal($date)
    {
        // Initialise
        $this->currentDay = Carbon::parse($date)->timezone('Australia/Tasmania')->startOfDay();
        $this->stage = 1;
        $this->clientText = 'Client';
        $this->projectText = 'Project';
        $this->projectID = null;
        $this->dateText = 'Date';
        $this->timeText = 'Time';
        $this->activity = null;

        $this->dispatch('open-modal', id: 'addEntryModal');
    }

    public function openEditEntryModal($id)
    {
        $this->activeEntry = TimeEntry::find($id);
        if ($this->activeEntry) {
            $data = $this->activeEntry->toArray();
            $data['client_id'] = (string)$this->activeEntry->client->id;
            $this->entryForm->fill($data);
        }

        $this->dispatch('open-modal', id: 'editEntryModal');
    }


    //
    // SAVING ENTRIES
    //
    public function saveNewEntry(): void
    {
        $project = Project::find($this->projectID);
        if ($project) {
            $data = [
                'project_id' => $this->projectID,
                'activity' => ($this->activity) ?? 'Support tickets',
                'date' => $this->currentDay,
                'start' => Carbon::createFromFormat('Y-m-d H:i', $this->currentDay->format('Y-m-d') . ' 09:00'),
                'end' => Carbon::createFromFormat('Y-m-d H:i', $this->currentDay->format('Y-m-d') . ' 09:00')->addMinutes((int)$this->timeText * 60),
                'duration' => $this->timeText * 60,
                'rate' => $project->rate ?? $project->client->rate,
                'entry_type'=> $this->timeType
            ];

            $this->activeEntry = TimeEntry::create($data);
        }

        $this->refreshCalendar();
        $this->dispatch('close-modal', id: 'addEntryModal');
        Notification::make()->success()->title('Entry created')->color('success')->send();
    }


    public function saveEntry(): void
    {
        $data = $this->entryForm->getState();
        unset($data['client_id']);

        // preview of the new values
        $data['activity'] = $data['activity'] ?? 'Support tickets';
        $data['rate'] = $this->activeEntry->project->rate ?? $this->activeEntry->client->rate;
        $data['start'] = Carbon::parse($this->currentDay->format('Y-m-d') . ' 09:00');
        $data['end']   = $data['start']->copy()->addMinutes((int)$this->timeText * 60);
        ray($data);

        // ---------------------------------------------------------
        // If linked to an invoice → require confirmation
        // ---------------------------------------------------------
        if ($this->activeEntry->invoice_id) {
            $this->pendingEditData = $data;
            $this->dispatch('open-modal', id: 'confirmEditEntryModal');
            return;
        }

        // If user already confirmed OR entry not linked → proceed
        $this->performEntryUpdate($data);
    }

    public function performEntryUpdate($array = null)
    {
        $data = $array ??  $this->pendingEditData;
        $this->activeEntry->update($data);
        $this->activeEntry->refresh();

        if ($this->activeEntry->invoice_id) {

            $invoiceItem = InvoiceItem::where('time_entry_id', $this->activeEntry->id)->first();

            if ($invoiceItem) {
                $rate = $this->activeEntry->rate
                    ?? $this->activeEntry->project->rate
                    ?? $this->activeEntry->client->rate;

                $rate = ($this->activeEntry->entry_type != 'payback') ? $rate : $rate * -1;
                $hours = $this->activeEntry->duration / 60;

                if ($invoiceItem->rate != $rate ||
                    $invoiceItem->hours != $hours ||
                    $invoiceItem->description != $this->activeEntry->project->name)
                {
                    $invoiceItem->update([
                        'rate'        => $rate,
                        'quantity'    => round($hours, 2),
                        'description' => $this->activeEntry->project->name,
                    ]);
                }
            }
        }

        // Reset confirm state
        $this->pendingEditData = [];

        $this->refreshCalendar();
        $this->dispatch('close-modal', id: 'confirmEditEntryModal');
        $this->dispatch('close-modal', id: 'editEntryModal');
        Notification::make()->success()->title('Entry updated')->send();
    }

    public function cancelInvoiceEdit(): void
    {
        $this->showInvoiceEditConfirm = false;
        $this->pendingEditData = [];
    }

    //
    // DELETE RESERVATIONS
    //
    public function confirmDeleteEntry($id)
    {
        $entry = TimeEntry::find($id);

        if ($entry) {
            $name = $entry->project->name . " (" . $entry->DurationHours . "h)";
            if ($entry->invoice_id)
                $name .= "<br><span class='text-red-500'>ALREADY INVOICED</span>";
            $this->deleteData = ['id' => $id, 'name' => $name];
            $this->dispatch('open-modal', id: 'deleteEntryModal');
        }
    }

    public function deleteEntry($id): void
    {
        $entry = TimeEntry::find($id);
        if (!$entry) return;

        $entry->delete();
        $this->deleteData = [];

        $this->refreshCalendar();
        $this->dispatch('close-modal', id: 'deleteEntryModal');
        $this->dispatch('close-modal', id: 'editEntryModal');
        Notification::make()->danger()->title('Entry deleted')->color('danger')->send();
    }


    public function getActiveClientsWithProjects(): array
    {
        $clients = Client::where('active', true)
            ->with(['projects' => function ($q) {$q->where('active', true)->orderBy('order');}])->orderBy('order')->get();

        $result = [];
        foreach ($clients as $client) {

            // Only include clients that have at least 1 active project
            if ($client->projects->isEmpty())
                continue;

            $result[$client->name] = $client->projects->pluck('name', 'id')->toArray();
        }

        return $result;
    }


    //
    // MONTHLY ENTRIES MAP
    //
    public function getEntriesForMonth(): array
    {
        $showWeekend = $this->showWeekend ?? false;

        $firstOfMonth = $this->currentMonth->copy()->startOfMonth();
        $lastOfMonth = $this->currentMonth->copy()->endOfMonth();
        $year = $this->currentMonth->format('Y');

        // Calendar grid boundaries
        $gridStart = $firstOfMonth->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $lastOfMonth->copy()->endOfWeek(Carbon::SUNDAY);

        $this->calFirstDay = $gridStart->copy();
        $this->calLastDay = $gridEnd->copy();

        // ---------------------------------------------
        // LOAD ALL ENTRIES FOR THE VISIBLE DATE RANGE
        // Group explicitly by Y-m-d string
        // ---------------------------------------------
        $entries = TimeEntry::whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString(),])
            ->orderBy('date')->get()->groupBy(function ($entry) {return Carbon::parse($entry->date)->format('Y-m-d');}); // Ensure we always use 'Y-m-d' as the key

        // ---------------------------------------------
        // LOAD ALL HOSTING + DOMAINS THAT REOCUR IN VISIBLE DATE RANGE
        // Group explicitly by Y-m-d string
        // ---------------------------------------------
        $currentYear = $this->currentMonth->year;
        $currentMonthNumber = $this->currentMonth->month;

        // HOSTING — recurring every year on same month/day
        $hosting = Hosting::whereMonth('next_renewal', $currentMonthNumber)->where('active', 1)->get()->groupBy(function ($entry) use ($currentYear) {
                $newDate = Carbon::parse($entry->date)->setYear($currentYear); // Replace original year with current year
                return $newDate->format('Y-m-d');});

        // DOMAINS — recurring every year on same month/day
        $domains = Domain::whereMonth('next_renewal', $currentMonthNumber)->where('active', 1)->get()->groupBy(function ($entry) use ($currentYear) {
                $newDate = Carbon::parse($entry->date)->setYear($currentYear);
                return $newDate->format('Y-m-d');});

        // ---------------------------------------------
        // BUILD PER-DAY SUMMARY
        // ---------------------------------------------
        $result = [];
        $cursor = $gridStart->copy();

        while ($cursor->lte($gridEnd)) {
            $date = $cursor->format('Y-m-d');  // "2025-12-18"
            $isWeekday = $cursor->isWeekday();

            // Skip weekends if hidden
            if (!$showWeekend && !$isWeekday) {
                $cursor->addDay();
                continue;
            }

            $inMonth = $cursor->month === $this->currentMonth->month;

            // Entries for this day (now keyed by Y-m-d, so this matches)
            $dayEntries = $entries->get($date, collect());
            $dayHosting = $hosting->get($date, collect());
            $dayDomains = $domains->get($date, collect());

            // Total duration (in hours)
            $totalMinutes = $dayEntries->sum('duration');
            $rawHours = $totalMinutes / 60;

            // Format hours: strip trailing .0 but keep decimals if needed
            $formattedHours = rtrim(rtrim(number_format($rawHours, 2, '.', ''), '0'), '.');

            // Hosting renewals


            // Highlight today
            $cellBG = $date === $this->today->format('Y-m-d') ? 'bg-yellow-50 dark:bg-yellow-900/30' : '';

            $result[$date] = [
                'day' => $cursor->day,
                'date' => $date,
                'inMonth' => $inMonth,
                'cellBG' => $cellBG,
                'entries' => $dayEntries,     // full collection of TimeEntry models
                'hours' => $formattedHours,   // numeric summary
                'hosting' => $dayHosting,
                'domains' => $dayDomains,
            ];

            $cursor->addDay();
        }
        return $result;
    }


    //
    // BUILD MONTH WEEKS (Mon–Fri or Mon–Sun)
    //
    protected function buildCalendar(): void
    {
        $month = $this->currentMonth->copy();
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();
        $showWeekend = $this->showWeekend ?? false;

        // Start on the Monday of the week that contains the 1st
        $cursor = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);

        $weeks = [];

        if ($showWeekend) {
            // ----------------------------------------
            // OPTION A: Full 7-day weeks (Mon–Sun)
            // ----------------------------------------
            while (true) {
                $week = [];
                $hasMonthDay = false;

                for ($i = 0; $i < 7; $i++) {
                    $date = $cursor->format('Y-m-d');
                    $inMonth = $cursor->month === $month->month;

                    if ($inMonth)
                        $hasMonthDay = true;

                    $week[] = [
                        'date' => $date,
                        'day' => $cursor->day,
                        'inMonth' => $inMonth,
                        'data' => $this->calEntries[$date] ?? null,
                    ];

                    $cursor->addDay();
                }

                if ($hasMonthDay)
                    $weeks[] = $week;

                // Once we've stepped past the end of the month,
                // we've already included the last relevant week.
                if ($cursor->gt($endOfMonth))
                    break;
            }
        } else {
            // ----------------------------------------
            // OPTION B: Work-week only (Mon–Fri)
            // ----------------------------------------
            while ($cursor <= $endOfMonth) {
                $week = [];
                $hasMonthDay = false;

                // Build 5-day week (Mon–Fri)
                for ($i = 0; $i < 5; $i++) {
                    $date = $cursor->format('Y-m-d');
                    $inMonth = $cursor->month === $month->month;

                    if ($inMonth)
                        $hasMonthDay = true;

                    $week[] = [
                        'date' => $date,
                        'day' => $cursor->day,
                        'inMonth' => $inMonth,
                        'data' => $this->calEntries[$date] ?? null,
                    ];

                    $cursor->addDay();
                }

                // Skip Saturday + Sunday completely
                $cursor->addDays(2);

                // Only keep weeks that contain at least one day in this month
                if ($hasMonthDay)
                    $weeks[] = $week;
            }
        }

        $this->calendarData = $weeks;
        //ray($weeks);
    }

    public function toggleWeekend(): void
    {
        $this->showWeekend = !$this->showWeekend;
        $this->refreshCalendar();
    }
}
