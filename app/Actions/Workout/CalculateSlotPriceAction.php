<?php

namespace App\Actions\Workout;

class CalculateSlotPriceAction
{
    /**
     * Calculate the price per slot for a workout.
     *
     * Formula: ceil(total_price / slots_total)
     * Using ceil ensures the coach doesn't lose money on rounding.
     *
     * @param  float  $totalPrice  Total price for the workout
     * @param  int  $slotsTotal  Number of available slots
     * @return int Price per slot (always rounded up)
     */
    public function execute(float $totalPrice, int $slotsTotal): int
    {
        if ($slotsTotal <= 0) {
            throw new \InvalidArgumentException('Slots total must be greater than 0');
        }

        return (int) ceil($totalPrice / $slotsTotal);
    }
}
