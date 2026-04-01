<?php

namespace App\Http\Controllers\Coach;

use App\Actions\Workout\CalculateSlotPriceAction;
use App\Actions\Workout\CancelWorkoutAction;
use App\Actions\Workout\PublishWorkoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\StoreWorkoutRequest;
use App\Http\Requests\Coach\UpdateWorkoutRequest;
use App\Models\City;
use App\Models\Sport;
use App\Models\User;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WorkoutController extends Controller
{
    /**
     * Display a list of coach's workouts.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Workout::class);

        $user = auth()->user();

        $query = Workout::where('coach_id', $user->id)
            ->with(['sport', 'city']);

        // Apply status filter if provided
        $status = request('status');
        if ($status && in_array($status, ['draft', 'published', 'cancelled', 'completed'], true)) {
            $query->where('status', $status);
        }

        $workouts = $query->orderBy('starts_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Coach/Workouts/Index', [
            'workouts' => $workouts,
            'filters' => [
                'status' => $status,
            ],
            'coachModerationStatus' => $user->coachProfile?->moderation_status,
        ]);
    }

    /**
     * Show the form for creating a new workout.
     */
    public function create(): Response
    {
        $this->authorize('create', Workout::class);

        $cities = City::orderBy('name')->get(['id', 'name']);
        $sports = Sport::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Coach/Workouts/Create', [
            'cities' => $cities,
            'sports' => $sports,
        ]);
    }

    /**
     * Store a newly created workout in storage.
     */
    public function store(StoreWorkoutRequest $request, CalculateSlotPriceAction $calculateSlotPrice): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        try {
            $workout = DB::transaction(function () use ($user, $validated, $calculateSlotPrice) {
                // Lock the coach's user record first to prevent empty-set race conditions
                User::where('id', $user->id)->lockForUpdate()->first();

                // Lock ALL coach's active workouts in consistent order (by ID) to prevent deadlocks
                $coachWorkouts = Workout::where('coach_id', $user->id)
                    ->whereIn('status', ['draft', 'published'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                // Check max active workouts
                if ($coachWorkouts->count() >= 10) {
                    throw ValidationException::withMessages([
                        'status' => 'Вы достигли максимального количества активных тренировок (10). Завершите или отмените существующие тренировки.',
                    ]);
                }

                // Recheck overlap validation with already-locked workouts
                $startsAt = Carbon::parse($validated['starts_at']);
                $endsAt = $startsAt->copy()->addMinutes($validated['duration_minutes']);
                $bufferStart = $startsAt->copy()->subMinutes(30);
                $bufferEnd = $endsAt->copy()->addMinutes(30);

                foreach ($coachWorkouts as $existingWorkout) {
                    $workoutStart = $existingWorkout->starts_at;
                    $workoutEnd = $existingWorkout->starts_at->copy()->addMinutes($existingWorkout->duration_minutes);

                    if ($workoutStart < $bufferEnd && $workoutEnd > $bufferStart) {
                        throw ValidationException::withMessages([
                            'starts_at' => 'У вас уже есть тренировка в это время. Минимальный интервал между тренировками - 30 минут.',
                        ]);
                    }
                }

                // Recheck same location and time conflict inside transaction
                // Note: No lock needed here - the unique constraint will catch race conditions
                $sameLocationAndTime = Workout::where('coach_id', '!=', $user->id)
                    ->whereIn('status', ['draft', 'published'])
                    ->where('starts_at', $startsAt->toDateTimeString())
                    ->where('lat', $validated['lat'])
                    ->where('lng', $validated['lng'])
                    ->exists();

                if ($sameLocationAndTime) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'На это время и место уже запланирована другая тренировка.',
                    ]);
                }

                // Calculate slot price using Action
                $slotPrice = $calculateSlotPrice->execute(
                    $validated['total_price'],
                    $validated['slots_total']
                );

                $workout = Workout::create([
                    'coach_id' => $user->id,
                    'sport_id' => $validated['sport_id'],
                    'city_id' => $validated['city_id'],
                    'title' => $validated['title'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'location_name' => $validated['location_name'],
                    'address' => $validated['address'] ?? null,
                    'lat' => $validated['lat'],
                    'lng' => $validated['lng'],
                    'starts_at' => $validated['starts_at'],
                    'duration_minutes' => $validated['duration_minutes'],
                    'total_price' => $validated['total_price'],
                    'slot_price' => $slotPrice,
                    'slots_total' => $validated['slots_total'],
                    'slots_booked' => 0,
                    'status' => 'draft',
                ]);

                Log::info('Workout created', [
                    'workout_id' => $workout->id,
                    'coach_id' => $user->id,
                    'status' => 'draft',
                    'starts_at' => $workout->starts_at->toIso8601String(),
                    'slots_total' => $workout->slots_total,
                    'slot_price' => $slotPrice,
                ]);

                return $workout;
            });

            return redirect()->route('coach.workouts.index')
                ->with('success', 'Тренировка создана как черновик');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (QueryException $e) {
            // Catch unique constraint violations from race conditions
            // 23505: PostgreSQL unique violation
            // MySQL 23000 is too broad (includes FK violations), so check message for specific constraint
            // SQLite reports column list: "UNIQUE constraint failed: workouts.starts_at, workouts.lat, workouts.lng, workouts.status_for_unique_check"
            if (
                $e->getCode() === '23505' ||
                str_contains($e->getMessage(), 'workouts_location_time_unique') ||
                (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'workouts_location_time_unique')) ||
                (str_contains($e->getMessage(), 'UNIQUE constraint failed') && str_contains($e->getMessage(), 'workouts.starts_at') && str_contains($e->getMessage(), 'workouts.lat') && str_contains($e->getMessage(), 'workouts.lng'))
            ) {
                Log::warning('Workout creation failed due to unique constraint violation (race condition)', [
                    'user_id' => $user->id,
                    'starts_at' => $validated['starts_at'] ?? null,
                    'lat' => $validated['lat'] ?? null,
                    'lng' => $validated['lng'] ?? null,
                ]);

                return redirect()->back()
                    ->withErrors(['starts_at' => 'На это время и место уже запланирована другая тренировка.'])
                    ->withInput();
            }

            throw $e;
        }
    }

    /**
     * Show the form for editing a workout.
     */
    public function edit(Workout $workout): Response
    {
        $this->authorize('update', $workout);

        $cities = City::orderBy('name')->get(['id', 'name']);
        $sports = Sport::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return Inertia::render('Coach/Workouts/Edit', [
            'workout' => $workout->load(['sport', 'city']),
            'cities' => $cities,
            'sports' => $sports,
        ]);
    }

    /**
     * Update the specified workout in storage.
     */
    public function update(UpdateWorkoutRequest $request, Workout $workout, CalculateSlotPriceAction $calculateSlotPrice): RedirectResponse
    {
        $this->authorize('update', $workout);

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($workout, $validated, $calculateSlotPrice) {
                // Lock the coach's user record first to prevent empty-set race conditions
                User::where('id', $workout->coach_id)->lockForUpdate()->first();

                // Lock ALL coach's active workouts in consistent order (by ID) to prevent deadlocks
                $allWorkouts = Workout::where('coach_id', $workout->coach_id)
                    ->whereIn('status', ['draft', 'published'])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

                // Find the target workout in the locked set
                $lockedWorkout = $allWorkouts->firstWhere('id', $workout->id);
                if (! $lockedWorkout) {
                    throw ValidationException::withMessages([
                        'status' => 'Тренировка не найдена или имеет некорректный статус.',
                    ]);
                }

                // Use the locked workout instance for updates
                $workout = $lockedWorkout;

                // Re-validate slots_total >= slots_booked inside transaction with fresh data
                if ($validated['slots_total'] < $workout->slots_booked) {
                    throw ValidationException::withMessages([
                        'slots_total' => 'Нельзя уменьшить количество мест ниже текущего количества бронирований (' . $workout->slots_booked . ')',
                    ]);
                }

                // Prevent changing core fields if workout has bookings
                if ($workout->slots_booked > 0) {
                    $coreFieldsChanged = (
                        $workout->lat != $validated['lat'] ||
                        $workout->lng != $validated['lng'] ||
                        $workout->starts_at->toDateTimeString() != Carbon::parse($validated['starts_at'])->toDateTimeString() ||
                        $workout->duration_minutes != $validated['duration_minutes'] ||
                        $workout->total_price != $validated['total_price'] ||
                        $workout->slot_price != $calculateSlotPrice->execute($validated['total_price'], $validated['slots_total'])
                    );

                    if ($coreFieldsChanged) {
                        throw ValidationException::withMessages([
                            'slots_booked' => 'Нельзя изменять местоположение, время или цену тренировки с существующими бронированиями.',
                        ]);
                    }
                }

                // Count active workouts excluding target
                $activeCount = $allWorkouts->where('id', '!=', $workout->id)->count();

                if ($activeCount >= 10) {
                    throw ValidationException::withMessages([
                        'status' => 'Вы достигли максимального количества активных тренировок (10). Завершите или отмените существующие тренировки.',
                    ]);
                }

                // Recheck overlap validation with already-locked workouts
                $startsAt = Carbon::parse($validated['starts_at']);
                $endsAt = $startsAt->copy()->addMinutes($validated['duration_minutes']);
                $bufferStart = $startsAt->copy()->subMinutes(30);
                $bufferEnd = $endsAt->copy()->addMinutes(30);

                foreach ($allWorkouts->where('id', '!=', $workout->id) as $existingWorkout) {
                    $workoutStart = $existingWorkout->starts_at;
                    $workoutEnd = $existingWorkout->starts_at->copy()->addMinutes($existingWorkout->duration_minutes);

                    if ($workoutStart < $bufferEnd && $workoutEnd > $bufferStart) {
                        throw ValidationException::withMessages([
                            'starts_at' => 'У вас уже есть тренировка в это время. Минимальный интервал между тренировками - 30 минут.',
                        ]);
                    }
                }

                // Recheck same location and time conflict inside transaction
                // Note: No lock needed here - the unique constraint will catch race conditions
                $sameLocationAndTime = Workout::where('coach_id', '!=', $workout->coach_id)
                    ->where('id', '!=', $workout->id)
                    ->whereIn('status', ['draft', 'published'])
                    ->where('starts_at', $startsAt->toDateTimeString())
                    ->where('lat', $validated['lat'])
                    ->where('lng', $validated['lng'])
                    ->exists();

                if ($sameLocationAndTime) {
                    throw ValidationException::withMessages([
                        'starts_at' => 'На это время и место уже запланирована другая тренировка.',
                    ]);
                }

                // Recalculate slot price using Action
                $slotPrice = $calculateSlotPrice->execute(
                    $validated['total_price'],
                    $validated['slots_total']
                );

                $workout->update([
                    'sport_id' => $validated['sport_id'],
                    'city_id' => $validated['city_id'],
                    'title' => $validated['title'] ?? null,
                    'description' => $validated['description'] ?? null,
                    'location_name' => $validated['location_name'],
                    'address' => $validated['address'] ?? null,
                    'lat' => $validated['lat'],
                    'lng' => $validated['lng'],
                    'starts_at' => $validated['starts_at'],
                    'duration_minutes' => $validated['duration_minutes'],
                    'total_price' => $validated['total_price'],
                    'slot_price' => $slotPrice,
                    'slots_total' => $validated['slots_total'],
                ]);

                Log::info('Workout updated', [
                    'workout_id' => $workout->id,
                    'coach_id' => $workout->coach_id,
                    'status' => $workout->status,
                    'starts_at' => $workout->starts_at->toIso8601String(),
                    'slots_total' => $workout->slots_total,
                    'slot_price' => $slotPrice,
                ]);
            });

            return redirect()->route('coach.workouts.index')
                ->with('success', 'Тренировка обновлена');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (QueryException $e) {
            // Catch unique constraint violations from race conditions
            // 23505: PostgreSQL unique violation
            // MySQL 23000 is too broad (includes FK violations), so check message for specific constraint
            // SQLite reports column list: "UNIQUE constraint failed: workouts.starts_at, workouts.lat, workouts.lng, workouts.status_for_unique_check"
            if (
                $e->getCode() === '23505' ||
                str_contains($e->getMessage(), 'workouts_location_time_unique') ||
                (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'workouts_location_time_unique')) ||
                (str_contains($e->getMessage(), 'UNIQUE constraint failed') && str_contains($e->getMessage(), 'workouts.starts_at') && str_contains($e->getMessage(), 'workouts.lat') && str_contains($e->getMessage(), 'workouts.lng'))
            ) {
                Log::warning('Workout update failed due to unique constraint violation (race condition)', [
                    'workout_id' => $workout->id,
                    'starts_at' => $validated['starts_at'] ?? null,
                    'lat' => $validated['lat'] ?? null,
                    'lng' => $validated['lng'] ?? null,
                ]);

                return redirect()->back()
                    ->withErrors(['starts_at' => 'На это время и место уже запланирована другая тренировка.'])
                    ->withInput();
            }

            throw $e;
        }
    }

    /**
     * Publish a workout.
     */
    public function publish(Workout $workout, PublishWorkoutAction $publishWorkoutAction): RedirectResponse
    {
        $this->authorize('publish', $workout);

        try {
            $publishWorkoutAction->execute($workout);

            return redirect()->route('coach.workouts.index')
                ->with('success', 'Тренировка успешно опубликована');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Cancel a workout.
     */
    public function cancel(Workout $workout, CancelWorkoutAction $cancelWorkoutAction): RedirectResponse
    {
        $this->authorize('cancel', $workout);

        try {
            $cancelWorkoutAction->execute($workout);

            return redirect()->route('coach.workouts.index')
                ->with('success', 'Тренировка успешно отменена');
        } catch (ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->with('error', $e->getMessage());
        }
    }
}
