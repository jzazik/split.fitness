<?php

namespace Tests\Unit\Actions;

use App\Actions\Workout\CalculateSlotPriceAction;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalculateSlotPriceActionTest extends TestCase
{
    private CalculateSlotPriceAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CalculateSlotPriceAction();
    }

    public function test_calculates_slot_price_with_even_division(): void
    {
        $result = $this->action->execute(1000, 10);
        $this->assertEquals(100, $result);
    }

    public function test_calculates_slot_price_with_rounding_up(): void
    {
        // 1000 / 3 = 333.33... → should round up to 334
        $result = $this->action->execute(1000, 3);
        $this->assertEquals(334, $result);
    }

    public function test_calculates_slot_price_for_single_slot(): void
    {
        $result = $this->action->execute(500, 1);
        $this->assertEquals(500, $result);
    }

    public function test_calculates_slot_price_with_small_amount(): void
    {
        // 10 / 3 = 3.33... → should round up to 4
        $result = $this->action->execute(10, 3);
        $this->assertEquals(4, $result);
    }

    public function test_calculates_slot_price_with_decimal_total_price(): void
    {
        // 99.99 / 5 = 19.998 → should round up to 20
        $result = $this->action->execute(99.99, 5);
        $this->assertEquals(20, $result);
    }

    public function test_throws_exception_for_zero_slots(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slots total must be greater than 0');

        $this->action->execute(1000, 0);
    }

    public function test_throws_exception_for_negative_slots(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Slots total must be greater than 0');

        $this->action->execute(1000, -5);
    }
}
