<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Domain;
use App\Models\Hosting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Project;
use App\Models\TimeEntry;
use Carbon\Carbon;
use DB;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use League\Csv\Reader;

class SetupController extends Controller
{

    public function quick()
    {
        echo "<h1>Quick</h1>";

        foreach (Domain::all() as $item) {
            if (!$item->date) {
                $this->warn("Skipping ID {$item->id} (no date)");
                continue;
            }

            // last_renewed = same month/day as original date but in 2025
            $lastRenewed = $item->date->copy()->year(2025);

            // next_renewal = last_renewed + renewal years
            $nextRenewal = $lastRenewed->copy()->addYears($item->renewal);

            $item->update([
                'last_renewed' => $lastRenewed,
                'next_renewal' => $nextRenewal,
            ]);

            echo "Domain #{$item->name}: last={$lastRenewed->toDateString()}, next={$nextRenewal->toDateString()}<br>";
        }

        foreach (Hosting::all() as $item) {
            if (!$item->date) {
                $this->warn("Skipping ID {$item->id} (no date)");
                continue;
            }

            // last_renewed = same month/day as original date but in 2025
            $lastRenewed = $item->date->copy()->year(2025);

            // next_renewal = last_renewed + renewal years
            $nextRenewal = $lastRenewed->copy()->addYears($item->renewal);

            $item->update([
                'last_renewed' => $lastRenewed,
                'next_renewal' => $nextRenewal,
            ]);

            echo "Hosting #{$item->id}: last={$lastRenewed->toDateString()}, next={$nextRenewal->toDateString()}<br>";
        }
    }

    public function dusty()
    {
        return view('dustyroad',);
    }

    public function mail()
    {
        echo "<h1>Testing mail</h1>";
        Mail::send('emails/jobstart', $data, function ($m) use ($email_list, $data, $file) {
            $send_from = 'do-not-reply@safeworksite.com.au';
            $m->from($send_from, 'Safe Worksite');
            $m->to($email_list);
            $m->subject('Upcoming Job Start Dates');
            $m->attach($file);
        });
    }

    public function setupDB()
    {
        echo "<h1>Setting up DB</h1>";

        echo "Resetting Tables...</br>";
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('clients')->truncate();
        DB::table('invoices')->truncate();
        DB::table('invoice_items')->truncate();
        DB::table('projects')->truncate();
        DB::table('time_entries')->truncate();
        DB::table('hosting')->truncate();
        DB::table('domains')->truncate();
        DB::table('products')->truncate();

        // Setup User
        $fudge = User::find('1');
        if (!$fudge)
            $fudge = User::create(['name' => 'fudge', 'email' => 'fudge@jordan.net.au', 'password' => Hash::make('3212')]);


        // Setup Clients
        $order = 1;
        $cc = Client::create(['name' => 'Cape Cod', 'code' => 'CC', 'contact' => 'Ross Thomson', 'email' => 'accounts1@capecod.com.au', 'phone' => '(02) 9849 4444',
            'address' => '4 / 426 Church St', 'suburb' => 'Parramatta', 'state' => 'NSW', 'postcode' => '2151', 'rate' => 85, 'active' => 1, 'order' => $order++]);
        $aoo = Client::create(['name' => 'AOO', 'code' => 'AO', 'contact' => 'Matt Gault', 'email' => 'admin@aoogroup.com', 'phone' => '0403 981 203',
            'address' => 'PO Box 7', 'suburb' => 'Round Corner', 'state' => 'NSW', 'postcode' => '2158', 'rate' => 85, 'active' => 1, 'order' => $order++]);
        $c3 = Client::create(['name' => 'C3 Convention Centre', 'code' => 'C3', 'contact' => 'Alexandra Jordan', 'email' => 'eventsmanager@c3hobart.org.au', 'phone' => '6122 0111',
            'address' => '64 Anglesea St', 'suburb' => 'South Hobart', 'state' => 'TAS', 'postcode' => '7004', 'rate' => 85, 'active' => 1, 'order' => $order++]);
        $mur = Client::create(['name' => 'Murray Jones', 'code' => 'MJ', 'contact' => 'Murray', 'email' => 'm.jones@cityhip.com.au', 'phone' => '0438 554 000',
            'address' => '612 Willoughby Rd', 'suburb' => 'Willoughby', 'state' => 'NSW', 'postcode' => '2068', 'rate' => 85, 'active' => 0, 'order' => $order++]);
        $asia = Client::create(['name' => 'Asia Advisory Associates', 'code' => 'LM', 'contact' => 'Llew Morris', 'email' => 'amor8215@bigpond.net.au', 'phone' => '0403 981 203',
            'address' => '12 Rydale Rd', 'suburb' => 'West Ryde', 'state' => 'NSW', 'postcode' => '2114', 'rate' => 85, 'active' => 0, 'order' => $order++]);
        $third = Client::create(['name' => 'Third Sector Advisory', 'code' => 'TS', 'contact' => 'Glyn Henman', 'email' => 'glyn.henman@outlook.com', 'phone' => '0412 432 274',
            'address' => '24 Gammell St', 'suburb' => 'Rydalmere ', 'state' => 'NSW', 'postcode' => '2115', 'rate' => 85, 'active' => 0, 'order' => $order++]);

        // Setup Project
        $order = 1;
        $p1 = Project::create(['client_id' => $cc->id, 'name' => 'SafeWorksite', 'rate' => 85, 'order' => $order++]);
        $p2 = Project::create(['client_id' => $cc->id, 'name' => 'Zoho', 'rate' => 85, 'order' => $order++]);
        $p3 = Project::create(['client_id' => $cc->id, 'name' => 'Additional Work', 'rate' => 85, 'order' => $order++]);
        $p4 = Project::create(['client_id' => $aoo->id, 'name' => 'Lynxoptics', 'rate' => 85, 'order' => $order++]);
        $p4 = Project::create(['client_id' => $aoo->id, 'name' => 'Wilderness Dreams', 'rate' => 85, 'order' => $order++]);
        $p5 = Project::create(['client_id' => $c3->id, 'name' => 'Booking System', 'rate' => 85, 'order' => $order++]);
        $p6 = Project::create(['client_id' => $mur->id, 'name' => 'Web Development', 'rate' => 85, 'order' => $order++]);
        $p7 = Project::create(['client_id' => $asia->id, 'name' => 'Web Development', 'rate' => 85, 'order' => $order++]);
        $p7 = Project::create(['client_id' => $third->id, 'name' => 'Web Development', 'rate' => 85, 'order' => $order++]);

        // Setup hosting
        $order = 1;
        $h1 = Hosting::create(['client_id' => $cc->id, 'name' => 'SafeWorksite', 'domain' => 'safeworksite.com.au', 'description' => 'Web hosting for safeworksite.com.au (1 year)', 'summary' => "includes SSL certificate", 'last_renewed' => '2025-01-28', 'next_renewal' => '2026-01-28',  'rate' => 2400, 'ssl' => 1, 'order' => $order++]);
        $h2 = Hosting::create(['client_id' => $aoo->id, 'name' => 'LynxOptics', 'domain' => 'lynxoptics.com.au', 'description' => 'Web hosting for lynxoptics.com.au (1 year)', 'summary' => "includes SSL certificate", 'last_renewed' => '2025-03-20', 'next_renewal' => '2026-03-20', 'rate' => 450, 'ssl' => 1, 'order' => $order++]);
        $h3 = Hosting::create(['client_id' => $aoo->id, 'name' => 'AOOgroup', 'domain' => 'aoogroup.com', 'description' => 'Web hosting for aoogroup.com (1 year)', 'summary' => null, 'last_renewed' => '2025-06-02', 'next_renewal' => '2026-03-20', 'rate' => 450, 'order' => $order++]);
        $h4 = Hosting::create(['client_id' => $c3->id, 'name' => 'C3 Booking', 'domain' => 'booking.c3beyond.org.au', 'description' => "Web hosting for C3 Booking System (1 year)", 'summary' => "includes SSL certificate", 'last_renewed' => '2025-03-01', 'next_renewal' => '2026-03-20', 'rate' => 450, 'order' => $order++]);

        // Setup domains
        $order = 1;
        $p1 = Domain::create(['client_id' => $cc->id, 'name' => 'safeworksite.com.au',  'last_renewed' => '2016-01-28', 'next_renewal' => '2016-01-28', 'rate' => 0, 'renewal' => '5', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'aoo.net.au',          'last_renewed' => '2015-02-12', 'next_renewal' => '2015-02-12', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'aoogroup.com',        'last_renewed' => '2015-02-16', 'next_renewal' => '2015-02-16', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'aoogroup.com.au',     'last_renewed' => '2015-02-16', 'next_renewal' => '2015-02-16', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'lynxoptics.com',      'last_renewed' => '2005-10-04', 'next_renewal' => '2005-10-04', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'lynxoptics.au',       'last_renewed' => '2023-07-18', 'next_renewal' => '2023-07-18', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'lynxoptics.co',       'last_renewed' => '2023-07-18', 'next_renewal' => '2023-07-18', 'rate' => 100, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'lynxoptics.co.nz',    'last_renewed' => '2003-11-26', 'next_renewal' => '2003-11-26', 'rate' => 100, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'lynxoptics.com.au',   'last_renewed' => '1999-08-30', 'next_renewal' => '1999-08-30', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'lynxopticsusa.com',   'last_renewed' => '2023-08-16', 'next_renewal' => '2023-08-16', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'lynx-optics.com',     'last_renewed' => '2005-10-04', 'next_renewal' => '2005-10-04', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'lynxplants.com',      'last_renewed' => '2023-12-08', 'next_renewal' => '2023-12-08', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'sunopticsaustralia.com',      'last_renewed' => '2013-09-06', 'last_renewed' => '2013-09-06', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'sunopticsaustralia.com.au',   'last_renewed' => '2013-09-06', 'last_renewed' => '2013-09-06', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'wildernessdreams.com.au',     'last_renewed' => '2013-02-04', 'last_renewed' => '2013-02-04', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $aoo->id, 'name' => 'wildernessdreams.co.nz',      'last_renewed' => '2013-02-04', 'last_renewed' => '2013-02-04', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $third->id, 'name' => 'thirdsectoradvisory.com',   'last_renewed' => '2022-07-25', 'last_renewed' => '2022-07-25', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);
        $p3 = Domain::create(['client_id' => $third->id, 'name' => 'thirdsectoradvisory.com.au', 'last_renewed' => '2022-07-25', 'last_renewed' => '2022-07-25', 'rate' => 50, 'renewal' => '2', 'order' => $order++]);


        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    public function importAll()
    {
        $this->import();
        $this->import2();
        $this->import3();
        $this->import4();
    }

    public function import()
    {
        $today = Carbon::today()->timezone('Australia/Tasmania');
        echo "<h1>Importing Timesheets</h1>";
        $this->setupDB();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('invoice_items')->truncate();
        DB::table('invoices')->truncate();
        DB::table('time_entries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $client = Client::findOrFail(1);

        // Project mappings
        $projects = [
            'safeworksite' => Project::findOrFail(1),
            'zoho' => Project::findOrFail(2),
            'databuild' => Project::findOrFail(3),
        ];

        $rateChange = Carbon::parse("1 July 2020");

        // Import directory
        $importPath = public_path("import");

        // Get all year CSVs, sorted oldest → newest
        $files = glob($importPath . "/*-*.csv");
        sort($files);

        if (empty($files)) {
            echo "No CSV files found in /public/import/<br>";
            return "Failed"; //Command::FAILURE;
        }

        foreach ($files as $filePath) {

            $filename = basename($filePath);
            echo "Importing: $filename<br>";

            $csv = Reader::createFromPath($filePath);
            $csv->skipEmptyRecords();
            $csv->setHeaderOffset(null);

            $i = 0;
            foreach ($csv->getRecords() as $row) {
                $i++;

                /* -----------------------------
                 * 1) Get & Validate AU Date
                 * ------------------------------*/
                $rawDate = trim($row[0] ?? '');

                if (!$this->isValidAuDate($rawDate)) {
                    continue; // ignore subtotal, blank, headers
                }

                try {
                    $date = Carbon::createFromFormat('d/m/Y', $rawDate);
                } catch (\Exception $e) {
                    continue;
                }
                //echo "[$i] " . $date->format('Y-m-d') .  " - ". trim($row[2] ?? '') . "<br>";

                /* -----------------------------
                 * 2) Extract Hours
                 * ------------------------------*/
                if (!is_numeric($row[1] ?? null)) continue;

                $hours = floatval($row[1]);
                if ($hours == 0) continue;
                $absHours = abs($hours);

                /* -----------------------------
                 * 3) Extract Activity
                 * ------------------------------*/
                $activity = trim($row[2] ?? '');
                if ($activity === '') {
                    $activity = 'General Work';
                }

                /* -----------------------------
                 * 4) Determine PROJECT
                 * ------------------------------*/
                $lower = strtolower($activity);

                if (str_contains($lower, 'zoho'))
                    $project = $projects['zoho'];
                elseif (str_contains($lower, 'other') || str_contains($lower, 'databuild'))
                    $project = $projects['databuild'];
                else
                    $project = $projects['safeworksite'];

                /* -----------------------------
                 * 5) Determine entry type
                 * ------------------------------*/
                $entryType = $hours > 0 ? 'regular' : 'payback';
                $rate = $date->lt($rateChange) ? 75 : 85;

                // Manually work out which are prebilled hours
                if ($date->format('Y-m-d') == '2016-09-30' && $absHours == 15) {
                    $entryType = 'prebill';
                    echo "found 15<br>";
                }
                if ($date->format('Y-m-d') == '2020-05-30' && $absHours == 20) {
                    $entryType = 'prebill';
                    echo "found 20<br>";
                }
                if ($date->format('Y-m-d') == '2020-06-30' && $absHours == 10) {
                    $entryType = 'prebill';
                    echo "found 10<br>";
                }
                if ($date->format('Y-m-d') == '2020-06-30' && $absHours == 65) {
                    $entryType = 'prebill';
                    echo "found 65<br>";
                }
                //if ($date->format('Y-m-d') == '2020-12-31' && $absHours == 35) {$entryType = 'prebill'; echo "found 35<br>";}
                if ($date->format('Y-m-d') == '2021-12-31' && $absHours == 20) {
                    $entryType = 'prebill';
                    echo "found 20<br>";
                }
                if ($date->format('Y-m-d') == '2022-08-31' && $absHours == 14) {
                    $entryType = 'prebill';
                    echo "found 14<br>";
                }
                if ($date->format('Y-m-d') == '2022-12-30' && $absHours == 45) {
                    $entryType = 'prebill';
                    echo "found 45<br>";
                }
                if ($date->format('Y-m-d') == '2023-09-30' && $absHours == 20) {
                    $entryType = 'prebill';
                    echo "found 20<br>";
                }
                if ($date->format('Y-m-d') == '2024-11-30' && $absHours == 10) {
                    $entryType = 'prebill';
                    echo "found 10<br>";
                }
                if ($date->format('Y-m-d') == '2025-01-31' && $absHours == 8) {
                    $entryType = 'prebill';
                    echo "found 8<br>";
                }
                if ($date->format('Y-m-d') == '2025-11-30' && $absHours == 8) {
                    $entryType = 'prebill';
                    echo "found 8<br>";
                }

                /* -----------------------------
                 * 6) Create fake start/end times
                 * ------------------------------*/
                $start = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' 09:00');
                $end = $start->copy()->addMinutes($absHours * 60);

                /* -----------------------------
                 * 7) Insert TimeEntry
                 * ------------------------------*/
                $entry = TimeEntry::create([
                    'project_id' => $project->id,
                    'activity' => $activity,
                    'date' => $date->format('Y-m-d'),
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                    'duration' => $absHours * 60, // minutes
                    'rate' => $rate,
                    'billable' => $absHours > 0,
                    'entry_type' => $entryType,
                    'import' => $date->format('Y-m-d') . " [$i]"
                ]);
            }
        }

        echo "All CSV imports completed!<br>";

        // Step A: find oldest and newest imported entry
        $firstDate = TimeEntry::orderBy('date')->first()?->date;
        $lastDate = TimeEntry::orderBy('date', 'desc')->first()?->date;

        if ($firstDate && $lastDate) {
            echo "Generating monthly invoices from $firstDate to $lastDate …<br>";

            $this->generateMonthlyInvoices(Client::find(1), Carbon::parse($firstDate), Carbon::parse($lastDate));
        }

        // Manual Time entries AOO
        $entry = TimeEntry::create([
            'project_id' => 4, 'activity' => 'LynxOptics cart security',
            'date' => '2025-12-01', 'start' => '09:00:00', 'end' => '11:00:00',
            'duration' => 2 * 60, 'rate' => 85, 'entry_type' => 'regular',
        ]);

        return "Success"; //Command::SUCCESS;
    }

    protected function generateMonthlyInvoices(Client $client, Carbon $startDate, Carbon $endDate): void
    {
        // Start from first-of-month of startDate
        $current = $startDate->copy()->startOfMonth();

        while ($current->lte($endDate)) {

            // Billing period is the *previous* month relative to the issue date
            $monthStart = $current->copy()->startOfMonth();
            $monthEnd = $current->copy()->endOfMonth();

            //echo "Creating invoice for {$monthStart->format('F Y')}<br>";

            // Fetch entries for this month that are not yet invoiced
            $entries = TimeEntry::whereNotNull('project_id')
                ->whereBetween('date', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d'),])
                ->where(function ($q) {
                    $q->whereNull('invoice_id')->orWhere('invoice_id', 0);
                })
                ->where('billable', true)
                ->orderBy('date')
                ->get();

            // No entries? Just proceed
            if ($entries->isEmpty()) {
                $current->addMonth();
                echo "Empty month<br>";
                continue;
            }

            // Create invoice for NEXT month (1st of next month)
            $invoiceIssue = $monthStart->copy()->addMonth()->startOfMonth();
            $invoiceDue = $invoiceIssue->copy()->addDays(7);
            $invoicePaid = $invoiceIssue->copy();

            $invoice = Invoice::create([
                'client_id' => $client->id,
                'issue_date' => $invoiceIssue->format('Y-m-d'),
                'due_date' => $invoiceDue->format('Y-m-d'),
                //'paid_date' => $invoicePaid->format('Y-m-d'),
                'status' => 'paid',
            ]);
            //echo "Created [$invoice->id] " . $invoice->issue_date->format('Y-m-d') . "<br>";

            // Add items
            foreach ($entries as $entry) {
                $rate = $entry->rate ?? $entry->project->rate ?? $client->rate;
                $rate = ($entry->entry_type != 'payback') ? $rate : $rate * -1;
                $hours = $entry->duration / 60;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'time_entry_id' => $entry->id,
                    'type' => 'time',
                    'description' => $entry->project->name,
                    'quantity' => round($hours, 2),
                    'rate' => $rate,
                ]);

                // Mark entry invoiced
                $entry->update(['invoice_id' => $invoice->id]);
            }

            $invoice->recalculateTotal();

            echo "Created invoice for {$monthStart->format('F Y')} with {$entries->count()} entries.<br>";

            // Move to next month
            $current->addMonth();
        }
    }

    private function isValidAuDate($value): bool
    {
        $value = trim($value);

        // must be d/m/Y, no mm-dd-yyyy, no blank, no text
        if (!preg_match('/^\d{1,2}\/\d{1,2}\/\d{4}$/', $value)) {
            return false;
        }

        // verify logical date
        $parts = explode('/', $value);
        return checkdate($parts[1], $parts[0], $parts[2]);
    }

    public function checkImport()
    {
        $valid = [
            '2014-08-01' => '1987.50',
            '2014-09-01' => '3600.00',
            '2014-10-01' => '412.50',
            '2014-11-01' => '637.50',
            '2014-12-01' => '5475.00',
            '2015-01-01' => '4500.00',
            '2015-02-01' => '337.50',
            '2015-03-01' => '6262.50',
            '2015-04-01' => '1837.50',
            '2015-05-01' => '4950.00',
            '2015-06-01' => '3787.50',

            '2015-07-01' => '3225.00',
            '2015-08-01' => '5925.00',
            '2015-09-01' => '3225.00',
            '2015-10-01' => '2062.50',
            '2015-11-01' => '3637.50',
            '2015-12-01' => '3825.00',
            '2016-01-01' => '7950.00',
            '2016-02-01' => '7162.50',
            '2016-03-01' => '3975.00',
            '2016-04-01' => '5025.00',
            '2016-05-01' => '5812.50',
            '2016-06-01' => '8700.00',
            '2016-07-01' => '9000.00',

            '2016-08-01' => '9262.50',
            '2016-09-01' => '11175.00',
            '2016-10-01' => '6150.00',
            '2016-11-01' => '1687.50',
            '2016-12-01' => '4987.50',
            '2017-01-01' => '3937.50',
            '2017-02-01' => '4087.50',
            '2017-03-01' => '5475.00',
            '2017-04-01' => '4725.00',
            '2017-05-01' => '3750.00',
            '2017-06-01' => '637.50',
            '2017-07-01' => '2062.50',
            '2017-08-01' => '4537.50',
            '2017-09-01' => '5287.50',
            '2017-10-01' => '5550.00',
            '2017-11-01' => '4987.50',
            '2017-12-01' => '7687.50',
            '2018-01-01' => '9562.50',
            '2018-02-01' => '9262.50',
            '2018-03-01' => '5512.50',
            '2018-04-01' => '7537.50',
            '2018-05-01' => '4612.50',
            '2018-06-01' => '5212.50',
            '2018-07-01' => '6825.00',
            '2018-08-01' => '937.50',
            '2018-09-01' => '2362.50',
            '2018-10-01' => '2475.00',
            '2018-11-01' => '3000.00',
            '2018-12-01' => '5700.00',
            '2019-01-01' => '2212.50',
            '2019-02-01' => '2925.00',
            '2019-03-01' => '1650.00',
            '2019-04-01' => '1125.00',
            '2019-05-01' => '600.00',
            '2019-06-01' => '1200.00',
            '2019-07-01' => '900.00',
            '2019-08-01' => '412.50',
            '2019-09-01' => '825.00',
            '2019-10-01' => '525.00',
            '2019-11-01' => '900.00',
            '2019-12-01' => '525.00',
            '2020-01-01' => '900.00',
            '2020-02-01' => '187.50',
            '2020-03-01' => '562.50',
            '2020-04-01' => '375.00',
            '2020-05-01' => '450.00',
            '2020-06-01' => '2362.50',
            '2020-07-01' => '6675.00',
            '2020-08-01' => '0.00',
            '2020-09-01' => '0.00',
            '2020-10-01' => '1997.50',
            '2020-11-01' => '3910.00',
            '2020-12-01' => '2125.00',
            '2021-01-01' => '4462.50',
            '2021-02-01' => '3782.50',
            '2021-03-01' => '4547.50',
            '2021-04-01' => '4632.50',
            '2021-05-01' => '1402.50',
            '2021-06-01' => '5610.00',
            '2021-07-01' => '2805.00',
            '2021-08-01' => '4207.50',
            '2021-09-01' => '3102.50',
            '2021-10-01' => '637.50',
            '2021-11-01' => '765.00',
            '2021-12-01' => '2337.50',
            '2022-01-01' => '2125.00',
            '2022-02-01' => '1615.00',
            '2022-03-01' => '5312.50',
            '2022-04-01' => '3442.50',
            '2022-05-01' => '4547.50',
            '2022-06-01' => '3145.00',
            '2022-07-01' => '5185.00',
            '2022-08-01' => '5312.50',
            '2022-09-01' => '4632.50',
            '2022-10-01' => '3485.00',
            '2022-11-01' => '425.00',
            '2022-12-01' => '5525.00',
            '2023-01-01' => '5482.50',
            '2023-02-01' => '3910.00',
            '2023-03-01' => '1232.50',
            '2023-04-01' => '2762.50',
            '2023-05-01' => '552.50',
            '2023-06-01' => '3315.00',
            '2023-07-01' => '1402.50',
            '2023-08-01' => '850',
            '2023-09-01' => '6545.00',
            '2023-10-01' => '4122.50',
            '2023-11-01' => '3527.50',
            '2023-12-01' => '2762.50',
            '2024-01-01' => '5567.50',
            '2024-02-01' => '2550.00',
            '2024-03-01' => '2847.50',
            '2024-04-01' => '3272.50',
            '2024-05-01' => '2677.50',
            '2024-06-01' => '5992.50',
            '2024-07-01' => '6545.00',
            '2024-08-01' => '4717.50',
            '2024-09-01' => '3782.50',
            '2024-10-01' => '6545.00',
            '2024-11-01' => '7097.50',
            '2024-12-01' => '4505.00',
            '2025-01-01' => '4080.00',
            '2025-02-01' => '2635.00',
            '2025-03-01' => '1487.50',
            '2025-04-01' => '2890.00',
            '2025-05-01' => '2762.50',
            '2025-06-01' => '2677.50',
            '2025-07-01' => '2422.50',
            '2025-08-01' => '3230.00',
            '2025-09-01' => '3910.00',
            '2025-10-01' => '4675.00',
            '2025-11-01' => '3740.00',
            '2025-12-01' => '3527.50',
        ];

        $invoices = Invoice::where('client_id', '1')->get();
        foreach ($invoices as $invoice) {
            $date = $invoice->issue_date->format('Y-m-d');
            $subtotal = $invoice->subtotal;
            $invtotal = isset($valid[$date]) ? $valid[$date] : '';
            if ($subtotal != $invtotal)
                echo $invoice->issue_date->format('Y-m-d') . " &nbsp; [$invtotal] => [$subtotal]<br>";
        }
    }

    public function import2()
    {
        $today = Carbon::today()->timezone('Australia/Tasmania');
        echo "<h1>Importing Timesheets Lynx</h1>";
        //$this->setupDB();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        //DB::table('invoice_items')->truncate();
        //DB::table('invoices')->truncate();
        //DB::table('time_entries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $client = Client::findOrFail(2);
        $rateChange = Carbon::parse("1 April 2025");

        // Get all year CSVs, sorted oldest → newest
        $files = glob(public_path("import") . "/lynx.csv");
        sort($files);

        if (empty($files)) {
            echo "No CSV files found in /public/import/<br>";
            return "Failed";
        }

        foreach ($files as $filePath) {

            $filename = basename($filePath);
            echo "Importing: $filename<br>";

            $csv = Reader::createFromPath($filePath);
            $csv->skipEmptyRecords();
            $csv->setHeaderOffset(null);

            $i = 0;
            foreach ($csv->getRecords() as $row) {
                $i++;

                /* -----------------------------
                 * 1) Get & Validate AU Date
                 * ------------------------------*/
                $rawDate = trim($row[0] ?? '');

                if (!$this->isValidAuDate($rawDate)) {
                    continue; // ignore subtotal, blank, headers
                }

                try {
                    $date = Carbon::createFromFormat('d/m/Y', $rawDate);
                } catch (\Exception $e) {
                    continue;
                }

                /* -----------------------------
                 * 2) Extract Hours
                 * ------------------------------*/
                if (!is_numeric($row[1] ?? null)) continue;

                $hours = floatval($row[1]);
                if ($hours == 0) continue;
                $absHours = abs($hours);

                /* -----------------------------
                 * 3) Extract Activity
                 * ------------------------------*/
                $activity = trim($row[2] ?? '');
                if ($activity === '') $activity = 'General Work';

                /* -----------------------------
                 * 4) Determine PROJECT
                 * ------------------------------*/
                $lower = strtolower($activity);
                $project = Project::find(4);

                /* -----------------------------
                 * 5) Determine entry type
                 * ------------------------------*/
                $entryType = $hours > 0 ? 'regular' : 'payback';
                $rate = $date->lt($rateChange) ? 75 : 85;

                /* -----------------------------
                 * 6) Create fake start/end times
                 * ------------------------------*/
                $start = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' 09:00');
                $end = $start->copy()->addMinutes($absHours * 60);

                /* -----------------------------
                 * 7) Insert TimeEntry
                 * ------------------------------*/
                $entry = TimeEntry::create([
                    'project_id' => $project->id,
                    'activity' => $activity,
                    'date' => $date->format('Y-m-d'),
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                    'duration' => $absHours * 60, // minutes
                    'rate' => $rate,
                    'billable' => $absHours > 0,
                    'entry_type' => $entryType,
                    'import' => $date->format('Y-m-d') . " [$i]"
                ]);
            }
        }

        echo "All CSV imports completed!<br>";

        // Step A: find oldest and newest imported entry
        $firstDate = TimeEntry::orderBy('date')->first()?->date;
        $lastDate = TimeEntry::orderBy('date', 'desc')->first()?->date;

        if ($firstDate && $lastDate) {
            echo "Generating invoices from $firstDate to $lastDate …<br>";

            $invoiceDates = ['2013-04-11', '2013-08-23', '2013-09-30', '2013-11-13',
                '2014-04-01', '2014-07-10', '2014-09-22', '2014-12-08',
                '2015-03-24', '2015-05-26', '2015-08-24', '2015-11-19',
                '2016-01-09', '2016-03-01', '2016-04-06', '2016-06-17', '2016-09-19', '2016-11-21', '2016-12-02',
                '2017-02-22', '2017-04-11', '2017-08-02', '2017-09-22', '2017-12-07',
                '2018-05-25', '2018-11-05', '2018-12-12',
                '2019-03-28', '2019-08-29',
                '2020-06-13', '2020-09-24',
                '2021-01-13', '2021-05-26', '2021-08-27',
                '2022-01-05', '2022-01-15', '2022-05-03',
                '2023-08-22', '2023-12-11',
                '2024-01-17',
            ];

            foreach ($invoiceDates as $date) {
                $invoiceIssue = Carbon::parse($date);
                $invoiceDue = Carbon::parse($date)->addDays(7);

                $invoice = Invoice::create([
                    'client_id' => $client->id,
                    'issue_date' => $invoiceIssue->format('Y-m-d'),
                    'due_date' => $invoiceDue->format('Y-m-d'),
                    'status' => 'paid',
                ]);


                // Fetch entries for this month that are not yet invoiced
                $entries = TimeEntry::whereDate('date', '<=', $date)
                    ->where(function ($q) {
                        $q->whereNull('invoice_id')->orWhere('invoice_id', 0);
                    })->orderBy('date')->get();

                // No entries? Just proceed
                if ($entries->isEmpty()) {
                    echo "Empty month<br>";
                    continue;
                }

                // Add Entries
                foreach ($entries as $entry) {
                    $rate = $entry->rate ?? $entry->project->rate ?? $client->rate;
                    $rate = ($entry->entry_type != 'payback') ? $rate : $rate * -1;
                    $hours = $entry->duration / 60;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'time_entry_id' => $entry->id,
                        'type' => 'time',
                        'description' => $entry->project->name,
                        'quantity' => round($hours, 2),
                        'rate' => $rate,
                    ]);

                    // Mark entry invoiced
                    $entry->update(['invoice_id' => $invoice->id]);
                }

                $invoice->recalculateTotal();

                echo "Created invoice for {$date} with {$entries->count()} entries.<br>";
            }
        }

        return "Success";
    }

    public function import3()
    {
        $today = Carbon::today()->timezone('Australia/Tasmania');
        echo "<h1>Importing Timesheets AOO</h1>";
        //$this->setupDB();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        //DB::table('invoice_items')->truncate();
        //DB::table('invoices')->truncate();
        //DB::table('time_entries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $client = Client::findOrFail(2);
        $rateChange = Carbon::parse("1 April 2025");

        // Get all year CSVs, sorted oldest → newest
        $files = glob(public_path("import") . "/aoo.csv");
        sort($files);

        if (empty($files)) {
            echo "No CSV files found in /public/import/<br>";
            return "Failed";
        }

        foreach ($files as $filePath) {

            $filename = basename($filePath);
            echo "Importing: $filename<br>";

            $csv = Reader::createFromPath($filePath);
            $csv->skipEmptyRecords();
            $csv->setHeaderOffset(null);

            $i = 0;
            foreach ($csv->getRecords() as $row) {
                $i++;

                /* -----------------------------
                 * 1) Get & Validate AU Date
                 * ------------------------------*/
                $rawDate = trim($row[0] ?? '');

                if (!$this->isValidAuDate($rawDate)) {
                    continue; // ignore subtotal, blank, headers
                }

                try {
                    $date = Carbon::createFromFormat('d/m/Y', $rawDate);
                } catch (\Exception $e) {
                    continue;
                }

                /* -----------------------------
                 * 2) Extract Hours
                 * ------------------------------*/
                if (!is_numeric($row[1] ?? null)) continue;

                $hours = floatval($row[1]);
                if ($hours == 0) continue;
                $absHours = abs($hours);

                /* -----------------------------
                 * 3) Extract Activity
                 * ------------------------------*/
                $activity = trim($row[2] ?? '');
                if ($activity === '') $activity = 'General Work';

                /* -----------------------------
                 * 4) Determine PROJECT
                 * ------------------------------*/
                $lower = strtolower($activity);
                $project = Project::find(5);

                /* -----------------------------
                 * 5) Determine entry type
                 * ------------------------------*/
                $entryType = $hours > 0 ? 'regular' : 'payback';
                $rate = $date->lt($rateChange) ? 75 : 85;

                /* -----------------------------
                 * 6) Create fake start/end times
                 * ------------------------------*/
                $start = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' 09:00');
                $end = $start->copy()->addMinutes($absHours * 60);

                /* -----------------------------
                 * 7) Insert TimeEntry
                 * ------------------------------*/
                $entry = TimeEntry::create([
                    'project_id' => $project->id,
                    'activity' => $activity,
                    'date' => $date->format('Y-m-d'),
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                    'duration' => $absHours * 60, // minutes
                    'rate' => $rate,
                    'billable' => $absHours > 0,
                    'entry_type' => $entryType,
                    'import' => $date->format('Y-m-d') . " [$i]"
                ]);
            }
        }

        echo "All CSV imports completed!<br>";

        // Step A: find oldest and newest imported entry
        $firstDate = TimeEntry::orderBy('date')->first()?->date;
        $lastDate = TimeEntry::orderBy('date', 'desc')->first()?->date;

        if ($firstDate && $lastDate) {
            echo "Generating invoices from $firstDate to $lastDate …<br>";

            $invoiceDates = [
                '2012-02-08', '2012-12-13',
                '2013-02-04', '2013-02-07', '2013-12-21',
                '2014-02-03', '2014-12-18',
                '2015-03-19', '2015-04-16', '2015-06-29', '2015-08-01', '2015-08-14', '2015-08-24', '2015-08-31',
                '2016-04-06', '2016-06-17',
                '2017-02-22', '2017-08-02',
                '2018-05-25',
                '2019-06-04',
                '2020-06-13',
                '2021-05-26',
                '2022-06-30',
                '2023-03-29', '2023-08-22',
                '2024-04-04', '2024-11-08',
                '2025-04-03', '2025-08-15',
            ];

            foreach ($invoiceDates as $date) {
                $invoiceIssue = Carbon::parse($date);
                $invoiceDue = Carbon::parse($date)->addDays(7);

                $invoice = Invoice::create([
                    'client_id' => $client->id,
                    'issue_date' => $invoiceIssue->format('Y-m-d'),
                    'due_date' => $invoiceDue->format('Y-m-d'),
                    'status' => 'paid',
                ]);


                // Fetch entries for this month that are not yet invoiced
                $entries = TimeEntry::whereDate('date', '<=', $date)->where(function ($q) {
                    $q->whereNull('invoice_id')->orWhere('invoice_id', 0);
                })->orderBy('date')->get();

                // No entries? Just proceed
                if ($entries->isEmpty()) {
                    echo "Empty month<br>";
                    continue;
                }

                // Add Entries
                foreach ($entries as $entry) {
                    $rate = $entry->rate ?? $entry->project->rate ?? $client->rate;
                    $rate = ($entry->entry_type != 'payback') ? $rate : $rate * -1;
                    $hours = $entry->duration / 60;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'time_entry_id' => $entry->id,
                        'type' => 'time',
                        'description' => $entry->project->name,
                        'quantity' => round($hours, 2),
                        'rate' => $rate,
                    ]);

                    // Mark entry invoiced
                    $entry->update(['invoice_id' => $invoice->id]);
                }

                $invoice->recalculateTotal();

                echo "Created invoice for {$date} with {$entries->count()} entries.<br>";
            }
        }
    }

    public function import4()
    {
        $today = Carbon::today()->timezone('Australia/Tasmania');
        echo "<h1>Importing Timesheets Murray</h1>";
        //$this->setupDB();

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        //DB::table('invoice_items')->truncate();
        //DB::table('invoices')->truncate();
        //DB::table('time_entries')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        $client = Client::findOrFail(4);
        $rateChange = Carbon::parse("30 November 2022");

        // Get all year CSVs, sorted oldest → newest
        $files = glob(public_path("import") . "/murray.csv");
        sort($files);

        if (empty($files)) {
            echo "No CSV files found in /public/import/<br>";
            return "Failed";
        }

        foreach ($files as $filePath) {

            $filename = basename($filePath);
            echo "Importing: $filename<br>";

            $csv = Reader::createFromPath($filePath);
            $csv->skipEmptyRecords();
            $csv->setHeaderOffset(null);

            $i = 0;
            foreach ($csv->getRecords() as $row) {
                $i++;

                /* -----------------------------
                 * 1) Get & Validate AU Date
                 * ------------------------------*/
                $rawDate = trim($row[0] ?? '');

                if (!$this->isValidAuDate($rawDate)) {
                    continue; // ignore subtotal, blank, headers
                }

                try {
                    $date = Carbon::createFromFormat('d/m/Y', $rawDate);
                } catch (\Exception $e) {
                    continue;
                }

                /* -----------------------------
                 * 2) Extract Hours
                 * ------------------------------*/
                if (!is_numeric($row[1] ?? null)) continue;

                $hours = floatval($row[1]);
                if ($hours == 0) continue;
                $absHours = abs($hours);

                /* -----------------------------
                 * 3) Extract Activity
                 * ------------------------------*/
                $activity = trim($row[2] ?? '');
                if ($activity === '') $activity = 'Web development';
                if ($activity == 'SPP') $activity = 'Sydney Painting Professionals';
                if ($activity == 'MPP') $activity = 'My Painting Professionals';
                if ($activity == 'TPP') $activity = 'The Painting Professionals';
                if ($activity == 'PPP') $activity = 'Pool Painting Professionals';
                if ($activity == 'SBP') $activity = 'Sydney Building Professionals';
                if ($activity == 'CH') $activity = 'CityHip';
                if ($activity == 'OPP') $activity = 'Our Pool Shop';
                if ($activity == 'IB') $activity = 'InsureBuild';
                if ($activity == 'MPT') $activity = 'My Pool Tiler';

                /* -----------------------------
                 * 4) Determine PROJECT
                 * ------------------------------*/
                $lower = strtolower($activity);
                $project = Project::find(7);

                /* -----------------------------
                 * 5) Determine entry type
                 * ------------------------------*/
                $entryType = $hours > 0 ? 'regular' : 'payback';
                $rate = $date->lt($rateChange) ? 75 : 85;

                /* -----------------------------
                 * 6) Create fake start/end times
                 * ------------------------------*/
                $start = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' 09:00');
                $end = $start->copy()->addMinutes($absHours * 60);

                /* -----------------------------
                 * 7) Insert TimeEntry
                 * ------------------------------*/
                $entry = TimeEntry::create([
                    'project_id' => $project->id,
                    'activity' => $activity,
                    'date' => $date->format('Y-m-d'),
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                    'duration' => $absHours * 60, // minutes
                    'rate' => $rate,
                    'billable' => $absHours > 0,
                    'entry_type' => $entryType,
                    'import' => $date->format('Y-m-d') . " [$i]"
                ]);
            }
        }

        echo "All CSV imports completed!<br>";

        // Step A: find oldest and newest imported entry
        $firstDate = TimeEntry::orderBy('date')->first()?->date;
        $lastDate = TimeEntry::orderBy('date', 'desc')->first()?->date;

        if ($firstDate && $lastDate) {
            echo "Generating invoices from $firstDate to $lastDate …<br>";

            $invoiceDates = [
                '2010-07-09', '2010-09-28',
                '2011-02-08', '2011-04-15', '2011-12-02',
                '2012-03-05', '2012-04-19',
                '2013-06-06',
                '2014-05-08', '2014-09-24',
                '2015-01-20', '2015-06-01',
                '2016-04-06',
                '2018-09-19',
                '2019-03-14', '2019-04-08',
                '2020-07-21',
                '2021-03-11', '2021-04-30',
                '2022-05-19', '2022-12-09',
                '2023-02-09', '2023-06-05',
                '2024-06-05',
            ];

            foreach ($invoiceDates as $date) {
                $invoiceIssue = Carbon::parse($date);
                $invoiceDue = Carbon::parse($date)->addDays(7);

                $invoice = Invoice::create([
                    'client_id' => $client->id,
                    'issue_date' => $invoiceIssue->format('Y-m-d'),
                    'due_date' => $invoiceDue->format('Y-m-d'),
                    'status' => 'paid',
                ]);


                // Fetch entries for this month that are not yet invoiced
                $entries = TimeEntry::whereDate('date', '<=', $date)->where(function ($q) {
                    $q->whereNull('invoice_id')->orWhere('invoice_id', 0);
                })->orderBy('date')->get();

                // No entries? Just proceed
                if ($entries->isEmpty()) {
                    echo "Empty month<br>";
                    continue;
                }

                // Add Entries
                foreach ($entries as $entry) {
                    $rate = $entry->rate ?? $entry->project->rate ?? $client->rate;
                    $rate = ($entry->entry_type != 'payback') ? $rate : $rate * -1;
                    $hours = $entry->duration / 60;

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'time_entry_id' => $entry->id,
                        'type' => 'time',
                        'description' => $entry->project->name,
                        'quantity' => round($hours, 2),
                        'rate' => $rate,
                    ]);

                    // Mark entry invoiced
                    $entry->update(['invoice_id' => $invoice->id]);
                }

                $invoice->recalculateTotal();

                echo "Created invoice for {$date} with {$entries->count()} entries.<br>";
            }
        }

        return "Success";
    }

}
