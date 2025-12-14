<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Client extends Model
{
    protected $fillable = ['name', 'code', 'contact', 'email', 'phone', 'address', 'suburb', 'state', 'postcode', 'rate', 'order', 'notes', 'active'];

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
    public function timeEntries(): HasManyThrough
    {
        return $this->hasManyThrough(TimeEntry::class, Project::class, 'client_id', 'project_id', 'id', 'id');
    }

    public function lastInvoiceDate()
    {
        $lastInvoice = Invoice::where('client_id', $this->id)->orderBy('issue_date', 'desc')->first();

        return ($lastInvoice) ? $lastInvoice->issue_date : Carbon::parse('2000-01-01');

    }

    public function hourBalance(): float
    {
        $prebill = 0;
        $payback = 0;

        foreach ($this->projects as $project) {
            foreach ($project->timeEntries as $entry) {
                if ($entry->entry_type === 'prebill')
                    $prebill += $entry->duration;
                elseif ($entry->entry_type === 'payback')
                    $payback += $entry->duration;

            }
        }

        // Convert to hours
        $hours = ($prebill - $payback) / 60;

        // Apply your custom adjustment (already in hours)
        $hours += $this->hourBalanceAdjustment;

        return $hours;
    }
    /**
     * Human label:
     *   > 0  = you owe hours
     *   = 0  = balanced
     *   < 0  = over-worked (client owes)
     */
    public function hourBalanceLabel(): string
    {
        $bal = $this->hourBalance();

        if ($bal > 0)
            return "{$bal} hours owed to client (pre-billed)";

        if ($bal < 0)
            return abs($bal)." hours extra delivered (client owes)";

        return "Balanced";
    }

    public function getHourBalanceAdjustmentAttribute(): float
    {
        return $this->id == 1 ? 44.5 : 0;
    }
}
