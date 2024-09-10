<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SalesorderDetail extends Model
{
    use HasFactory;

    protected $table = 'salesorder_detail';

    public $timestamps = false;
    protected $fillable = [
        'salesorder_id',
        'product_id',
        'quantity',
        'price',
        'status',
        'quantity_sent',
    ];

    

    public function salesorder()
    {
        return $this->belongsTo(SalesOrder::class, 'salesorder_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // public static function checkAndUpdateStatus($salesOrderId, $productId, $quantity)
    // {
    //     $details = self::where('salesorder_id', $salesOrderId)
    //         ->where('product_id', $productId)
    //         ->get();

    //     $totalQuantitySent = $details->sum('quantity_sent') + $quantity;

    //     $totalQuantity = $details->sum('quantity');
        
    //     if ($totalQuantitySent >= $totalQuantity) {
    //         $salesOrder = SalesOrder::find($salesOrderId);
    //         if ($salesOrder) {
    //             $salesOrder->status = 'completed';
    //             $salesOrder->save();
    //         }

    //         return true;
    //     }

    // }
    public static function checkAndUpdateStatus($salesOrderId, $productId, $quantity)
    {
        // Start a transaction to ensure atomicity
        DB::beginTransaction();
    
        try {
            // Fetch the sales order details for the given sales order and product
            $details = self::where('salesorder_id', $salesOrderId)
                ->where('product_id', $productId)
                ->get();
    
            // Calculate the total quantity already sent and add the new quantity
            $totalQuantitySent = $details->sum('quantity_sent') + $quantity;
    
            // Calculate the total quantity required
            $totalQuantity = $details->sum('quantity');
    
            // Fetch the corresponding sales order
            $salesOrder = SalesOrder::find($salesOrderId);
    
            if (!$salesOrder) {
                throw new \Exception('Sales order not found.');
            }
    
            // Check if the total quantity sent meets or exceeds the total quantity required
            if ($totalQuantitySent >= $totalQuantity) {
                // Update the sales order status to completed
                $salesOrder->status = 'completed';
            } else {
                // Update the sales order status to pending
                $salesOrder->status = 'pending';
            }
    
            // Save the sales order status
            $salesOrder->save();
    
            // Commit the transaction
            DB::commit();
    
            return true;
    
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollback();
    
            // Handle the exception (log it, rethrow it, etc.)
            return false;
        }
    }
    
    public function getQuantitySentAttribute()
    {
        // Directly use the quantity_sent attribute if available
        return $this->attributes['quantity_sent'] ?? 0;
    }
    public function getQuantityRemainingAttribute() {
        return $this->quantity - $this->quantity_sent;
    }

    protected static function boot()
    {
        parent::boot();

        // Listen for model updates
        static::updated(function ($detail) {
            // Update related sales order status
            $salesOrder = SalesOrder::find($detail->salesorder_id);
            if ($salesOrder) {
                self::updateSalesOrderStatus($salesOrder);
            }
        });
    }
    
    // public function getQuantitySentAttribute() {
    //     $result = 0;
    //     foreach ($this->shipmentDetails as $det) {
    //         $result += $det->qty;
    //         $result += $det->quantity;
    //     }
    //     return $result;
    // }
}
