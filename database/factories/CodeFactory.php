<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CodeFactory
{
    /**
     * Generate a unique sales order code.
     *
     * @return string
     */
    public static function generateSalesOrderCode()
    {
        // Get today's date in the format dd-mm-yy
        $datePrefix = Carbon::now()->format('d-m-y');

        // Get the latest sales order code to determine the next sequential number
        $latestOrder = DB::table('mstr_salesOrder')
            ->where('code', 'like', "{$datePrefix}-SO-%")
            ->orderBy('code', 'desc')
            ->first();

        // Determine the next sequential number
        if ($latestOrder) {
            // Extract the sequential number from the latest order code
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // If no previous orders, start with 0001
            $nextNumber = '0001';
        }

        // Generate the new sales order code
        return "{$datePrefix}-SO-{$nextNumber}";
    }

    public static function generateSalesInvoiceCode()
    {
        $datePrefix = Carbon::now()->format('d-m-y');

        $latestOrder = DB::table('invoice_sales')
            ->where('code', 'like', "{$datePrefix}-SI-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($latestOrder) {
            $lastNumber = (int)substr($latestOrder->code, -4);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return "{$datePrefix}-SI-{$nextNumber}";
    }
}
