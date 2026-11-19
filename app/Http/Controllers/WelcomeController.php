<?php

namespace App\Http\Controllers;

use App\Models\System;
use App\Models\Version;
use Illuminate\View\View;

class WelcomeController extends Controller
{
    public function index(): View
    {
        $systems = System::query()
            ->where('status', 'active')
            ->with('latestVersion')
            ->latest('updated_at')
            ->get();

        $stats = [
            'active' => $systems->count(),
            'stacks' => $systems->pluck('stack')->filter()->unique()->count(),
            'releases' => Version::query()
                ->whereHas('system', fn ($query) => $query->where('status', 'active'))
                ->count(),
        ];

        return view('welcome', [
            'featuredSystems' => $systems->take(6),
            'stats' => $stats,
        ]);
    }
}
