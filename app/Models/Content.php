<?php
// app/Models/Content.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $connection = 'sqlite2';
    protected $table = 'new_content';

    public function item()
    {
        // FK on this table = 'SKU', owner key on Items = 'sku'
        return $this->belongsTo(Items::class, 'sku', 'sku');
    }
}
