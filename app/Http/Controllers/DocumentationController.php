<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    public function userGuide(): Response
    {
        return Inertia::render('features/docs/pages/UserGuide');
    }

    public function developerGuide(): Response
    {
        return Inertia::render('features/docs/pages/DeveloperGuide');
    }
}
