<?php
namespace App\Services;

class CommissionCalculator
{
    /**
     * @param string $type 'percent' | 'per_order'
     * @param float  $rate  percentage (percent) or flat amount per order (per_order)
     */
    public function owed(string $type, float $rate, float $grossSales, int $orderCount): float
    {
        return match ($type) {
            'per_order' => round($orderCount * $rate, 2),
            default     => round($grossSales * ($rate / 100), 2),
        };
    }
}
