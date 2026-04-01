<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Coach/Payments');
    }
}
