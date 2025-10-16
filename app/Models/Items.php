<?php
// app/Models/Items.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Items extends Model
{
    protected $connection = 'sqlite'; // or 'sqlite' or whatever your default connection is
    protected $table = 'items';

    // keep default PK 'id' since your row shows id: 8871
    public $incrementing = true;   // optional, default true
    protected $keyType = 'int';    // optional, default int

    public function content()
    {
        // FK on Content = 'SKU', local owner key here = 'sku'
        return $this->hasOne(Content::class, 'sku', 'sku');
    }
    public function image()
    {
        // FK on Content = 'SKU', local owner key here = 'sku'
        return $this->hasOne(ItemImage::class, 'sku', 'sku');
    }
    public function meta()
    {
        // FK on Content = 'SKU', local owner key here = 'sku'
        return $this->hasOne(ProductMeta::class, 'sku', 'sku');
    }

    public function approval()
    {
        // FK on Content = 'SKU', local owner key here = 'sku'
        return $this->hasOne(ItemApproval::class, 'sku', 'sku');
    }

}
