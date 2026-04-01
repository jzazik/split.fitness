<?php

namespace App\Http\Controllers\Coach;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PaymentsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Coach/Payments');
    }
}
