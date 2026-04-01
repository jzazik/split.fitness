<?php

namespace App\Http\Controllers\Coach;

use App\Actions\Workout\CalculateSlotPriceAction;
use App\Actions\Workout\PublishWorkoutAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coach\StoreWorkoutRequest;
use App\Models\City;
use App\Models\Sport;
use App\Models\Workout;
use Illuminate\Http\RedirectResponse;
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
        $user = auth()->user();

        $query = Workout::where('coach_id', $user->id)
            ->with(['sport', 'city']);

        // Apply status filter if provided
        $status = request('status');
        if ($status && in_array($status, ['draft', 'published', 'cancelled', 'completed'])) {
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

        return redirect()->route('coach.workouts.index')
            ->with('success', 'Тренировка создана как черновик');
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
}
