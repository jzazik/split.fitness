<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\Sport;
use Inertia\Inertia;
use Inertia\Response;

class PublicMapController extends Controller
{
    /**
     * Display the public map page.
     */
    public function index(): Response
    {
        $cities = City::select('id', 'name', 'lat', 'lng')
            ->orderBy('name')
            ->get();

        $sports = Sport::select('id', 'name', 'slug', 'icon')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Public/Map/Index', [
            'cities' => $cities,
            'sports' => $sports,
        ]);
    }
}
