<?php

namespace App\Http\Controllers\Athlete;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class BookingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Athlete/Bookings/Index');
    }
}
