<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceDetail extends Model
{
    use HasFactory;

    // Define the table name
    protected $table = 'invoicesales_detail';

    public $timestamps = false;

    protected $dates = ['date'];

    // Define fillable fields
    protected $fillable = [
        'invociesales_id',
        'product_id',
        'quantity',
        'price',
        'status',
    ];

    // Define relationships
    public function salesinvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoicesales_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the latest sales order ID.
     *
     * @return int|null
     */
    public static function getLatestId()
    {
        return self::orderBy('id', 'desc')->pluck('id')->first();
    }


    
}
