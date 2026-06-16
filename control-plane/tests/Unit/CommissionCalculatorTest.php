<?php
namespace Tests\Unit;

use App\Services\CommissionCalculator;
use PHPUnit\Framework\TestCase;

class CommissionCalculatorTest extends TestCase
{
    public function test_percent_commission(): void
    {
        // 10% of 1000 gross = 100
        $this->assertEquals(100.00, (new CommissionCalculator)->owed('percent', 10, 1000.00, 7));
    }

    public function test_per_order_commission(): void
    {
        // $2.50 flat per order, 7 orders = 17.50 (gross ignored)
        $this->assertEquals(17.50, (new CommissionCalculator)->owed('per_order', 2.50, 1000.00, 7));
    }

    public function test_zero_rate_returns_zero(): void
    {
        $this->assertEquals(0.0, (new CommissionCalculator)->owed('percent', 0, 1000.00, 7));
    }
}
