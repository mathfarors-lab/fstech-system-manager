<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Build;
use App\Models\System;
use Illuminate\Http\Request;

class BuildController extends Controller
{
    public function store(Request $request, System $system)
    {
        $validated = $request->validate([
            'version_label' => ['required', 'string', 'max:50'],
            'version_id' => ['nullable', 'exists:versions,id'],
            'environment' => ['required', 'in:development,staging,production'],
            'status' => ['required', 'in:planned,building,deployed,failed,rolled_back'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $now = now();
        $validated['built_by_id'] = $request->user()->id;
        $validated['built_at'] = in_array($validated['status'], ['building', 'deployed']) ? $now : null;
        $validated['deployed_at'] = $validated['status'] === 'deployed' ? $now : null;

        $system->builds()->create($validated);

        return back()->with('success', "Build {$validated['version_label']} added.");
    }

    public function update(Request $request, System $system, Build $build)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:planned,building,deployed,failed,rolled_back'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $build->fill($validated);

        if (in_array($build->status, ['building', 'deployed']) && ! $build->built_at) {
            $build->built_at = now();
        }

        if ($build->status === 'deployed' && ! $build->deployed_at) {
            $build->deployed_at = now();
        }

        $build->save();

        return back()->with('success', "Build {$build->version_label} updated to \"{$build->statusLabel()}\".");
    }

    public function destroy(System $system, Build $build)
    {
        $build->delete();

        return back()->with('success', 'Build deleted.');
    }
}
