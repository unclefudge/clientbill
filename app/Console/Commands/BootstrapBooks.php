<?php

namespace App\Console\Commands;

use App\Models\BookBusiness;
use App\Models\BookFinancialYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BootstrapBooks extends Command
{
    protected $signature = 'books:bootstrap
        {--user-email=fudge@jordan.net.au : Existing ClientBill user to make the Books owner}
        {--business=Open Hands Trust : Business display name}
        {--abn=15 420 079 851 : Australian Business Number}
        {--gst : Mark the business as GST registered}';

    protected $description = 'Create the initial Books business and give an existing ClientBill user access';

    public function handle(): int
    {
        $user = User::where('email', $this->option('user-email'))->first();

        if (! $user) {
            $this->error('No user found with email ' . $this->option('user-email'));
            return self::FAILURE;
        }

        $business = BookBusiness::firstOrCreate(
            ['name' => $this->option('business')],
            [
                'legal_name' => $this->option('business'),
                'abn' => $this->option('abn') ?: null,
                'gst_registered' => (bool) $this->option('gst'),
                'gst_rate' => 10,
                'bas_frequency' => 'quarterly',
                'active' => true,
            ]
        );

        $business->users()->syncWithoutDetaching([
            $user->id => ['role' => 'owner'],
        ]);

        $today = now('Australia/Hobart');
        $fyStartYear = $today->month >= 7 ? $today->year : $today->year - 1;
        $fyStart = Carbon::create($fyStartYear, 7, 1);
        $fyEnd = Carbon::create($fyStartYear + 1, 6, 30);

        BookFinancialYear::firstOrCreate(
            [
                'book_business_id' => $business->id,
                'start_date' => $fyStart->toDateString(),
                'end_date' => $fyEnd->toDateString(),
            ],
            [
                'label' => $fyStartYear . '–' . substr((string) ($fyStartYear + 1), -2),
                'status' => 'open',
            ]
        );

        $user->forceFill([
            'can_access_billing' => true,
            'can_manage_users' => true,
            'default_area' => 'billing',
            'default_book_business_id' => $business->id,
        ])->save();

        $this->info('Books is ready for ' . $business->name . '.');
        $this->line('User: ' . $user->name . ' <' . $user->email . '>');
        $this->line('GST registered: ' . ($business->gst_registered ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
