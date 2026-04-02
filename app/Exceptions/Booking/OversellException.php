<?php

namespace App\Exceptions\Booking;

use Exception;

class OversellException extends Exception
{
    public function __construct(
        public readonly int $workoutId,
        public readonly int $slotsRequested,
        public readonly int $slotsBooked,
        public readonly int $slotsTotal,
        string $message = 'Недостаточно мест для бронирования.'
    ) {
        parent::__construct($message);
    }
}
