<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemImage extends Model
{
    protected $connection = 'sqlite2'; // or 'sqlite' or whatever your default connection is
    protected $table = 'product_images';

    public function item()
    {
        // FK on this table = 'SKU', owner key on Items = 'sku'
        return $this->belongsTo(Items::class, 'sku', 'sku');
    }
}
