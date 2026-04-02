<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionRetry
{
    /**
     * Execute a callback within a database transaction with retry logic for deadlocks.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     *
     * @throws QueryException
     */
    public static function execute(callable $callback, int $maxAttempts = 3)
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction($callback);
            } catch (QueryException $e) {
                // Check if this is a deadlock error
                // MySQL: 1213, 1205
                // PostgreSQL: 40P01, 40001
                // SQLite: SQLITE_BUSY (5)
                $isDeadlock = in_array($e->getCode(), ['40P01', '40001', '1213', '1205', '5']) ||
                    str_contains($e->getMessage(), 'Deadlock') ||
                    str_contains($e->getMessage(), 'deadlock') ||
                    str_contains($e->getMessage(), 'database is locked');

                if (! $isDeadlock || $attempt >= $maxAttempts) {
                    // Not a deadlock or max retries reached, rethrow
                    throw $e;
                }

                // Log the retry attempt
                Log::warning('Transaction deadlock detected, retrying', [
                    'attempt' => $attempt,
                    'max_attempts' => $maxAttempts,
                    'error_code' => $e->getCode(),
                    'error_message' => $e->getMessage(),
                ]);

                // Small delay before retry (exponential backoff)
                usleep(min(100000 * (2 ** ($attempt - 1)), 500000)); // 100ms, 200ms, 400ms max
            }
        }
    }
}
