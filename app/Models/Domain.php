<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Domain extends Model
{
    protected $fillable = ['client_id', 'name', 'description', 'summary', 'date', 'last_renewed', 'next_renewal', 'rate', 'renewal', 'order', 'active',];
    protected $casts = ['date' => 'date', 'last_renewed' => 'date', 'next_renewal' => 'date',];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS / HELPERS
    |--------------------------------------------------------------------------
    */

    /**
     * Ensure next_renewal is set based on last_renewed + renewal.
     * Does not save automatically.
     */
    public function syncNextRenewalFromLast(): void
    {
        if (!$this->last_renewed)
            return;

        $this->next_renewal = $this->last_renewed->copy()->addYears($this->renewal);
    }

    /**
     * Get the next renewal date relative to "now" if none is set.
     * (Safe helper, does NOT persist anything.)
     */
    public function getComputedNextRenewalDate(?Carbon $from = null): ?Carbon
    {
        $from ??= now();

        // If next_renewal is already stored, respect it
        if ($this->next_renewal)
            return $this->next_renewal->copy();

        // If we have a last_renewed, derive from that
        if ($this->last_renewed) {
            $cursor = $this->last_renewed->copy();

            while ($cursor->lte($from))
                $cursor->addYears($this->renewal);

            return $cursor;
        }

        // Fallback: derive from original "date"
        if ($this->date) {
            $cursor = $this->date->copy();

            while ($cursor->lte($from))
                $cursor->addYears($this->renewal);

            return $cursor;
        }

        return null;
    }

    public function markAsRenewed(): void
    {
        $this->last_renewed = $this->next_renewal;
        $this->syncNextRenewalFromLast();
        $this->save();
    }

    /*
    |--------------------------------------------------------------------------
    | STATIC HELPERS FOR CLIENT / RANGE
    |--------------------------------------------------------------------------
    */

    /**
     * Return domains for a client that renew within a given date range,
     * based primarily on next_renewal.
     */
    public static function forClientRenewingBetween($clientId, $start, $end) {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate   = Carbon::parse($end)->endOfDay();

        return static::where('active', 1)->where('client_id', $clientId)->whereNotNull('next_renewal')->whereBetween('next_renewal', [$startDate, $endDate])->get();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES FOR DASHBOARD (SQL OPTIMISED)
    |--------------------------------------------------------------------------
    */


    // Scope for any that are coming up for renewal in x days
    public function scopeRenewingWithinDays(Builder $query, int $days): Builder
    {
        $now    = now()->startOfDay();
        $future = $now->copy()->addDays($days)->endOfDay();

        return $query->whereNotNull('next_renewal')->whereBetween('next_renewal', [$now, $future]);
    }
}
