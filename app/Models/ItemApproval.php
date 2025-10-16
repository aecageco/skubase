<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemApproval extends Model
{
    protected $fillable = [
        'sku',
        'status',
        'approved_by',
        'approved_at',
    ];

    public function item()
    {
        // FK on this table = 'SKU', owner key on Items = 'sku'
        return $this->belongsTo(Items::class, 'sku', 'sku');
    }
}
