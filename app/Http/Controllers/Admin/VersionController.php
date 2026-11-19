<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\System;
use App\Models\Version;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function store(Request $request, System $system)
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:50'],
            'release_date' => ['nullable', 'date'],
            'changelog' => ['nullable', 'string', 'max:10000'],
        ]);

        $system->versions()->firstOrCreate(
            ['version' => $validated['version']],
            array_filter($validated, fn ($k) => $k !== 'version', ARRAY_FILTER_USE_KEY)
        );

        return back()->with('success', "Version {$validated['version']} added.");
    }

    public function update(Request $request, System $system, Version $version)
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:50'],
            'release_date' => ['nullable', 'date'],
            'changelog' => ['nullable', 'string', 'max:10000'],
        ]);

        $version->update($validated);

        return back()->with('success', 'Version updated.');
    }

    public function destroy(System $system, Version $version)
    {
        $version->delete();

        return back()->with('success', 'Version deleted.');
    }
}
