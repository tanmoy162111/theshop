<?php
namespace App\Agent;

use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class SalesAggregator
{
    /** @return array{gross_sales: float, order_count: int} */
    public function forPeriod(CarbonInterface $start, CarbonInterface $end): array
    {
        $q = DB::table('orders')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end]);

        return [
            'gross_sales' => (float) $q->sum('grand_total'),
            'order_count' => (int) $q->count(),
        ];
    }
}
