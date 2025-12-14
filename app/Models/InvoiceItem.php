<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = ['invoice_id', 'type', 'description', 'summary', 'quantity', 'rate', 'amount', 'order', 'time_entry_id', 'hosting_id', 'domain_id', 'product_id',];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

    public function hosting(): BelongsTo
    {
        return $this->belongsTo(Hosting::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function project()
    {
        return $this->hasOneThrough(Project::class, TimeEntry::class, 'id', 'id', 'time_entry_id', 'project_id');
    }

    protected static function booted()
    {
        static::saving(function (InvoiceItem $item) {
            // If linked to a product, inherit name + rate
            if ($item->product_id && (!$item->description || $item->rate == 0)) {
                $item->description = $item->product->name;
                $item->rate = $item->product->rate;
            }

            $item->amount = $item->quantity * $item->rate;
        });

        static::deleted(function (InvoiceItem $item) {
            // If this invoice item came from a time entry, un-link it
            if ($item->time_entry_id) {
                $entry = $item->timeEntry;

                if ($entry) {
                    $entry->invoice_id = null;
                    $entry->billable = true;
                    $entry->save();
                }
            }
        });
    }
}
