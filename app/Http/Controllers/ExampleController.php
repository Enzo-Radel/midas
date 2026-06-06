<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ExampleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Example', [
            'message' => 'Hello from Inertia + Vue!',
        ]);
    }
}
