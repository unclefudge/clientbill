<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'rate', 'description', 'order', 'active',];

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
