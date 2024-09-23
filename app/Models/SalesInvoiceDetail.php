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
        'invoicesales_id',
        'product_id',
        'quantity',
        'price',
        'status',
        'salesdetail_id',
    ];

    // Define relationships
    public function salesinvoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'invoicesales_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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

    public function salesOrderDetail()
    {
        return $this->belongsTo(SalesOrderDetail::class, 'salesdetail_id');
    }
    
        public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class, 'salesorder_id');
    }
}
