<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OperationLog;
use App\Models\System;
use Illuminate\Http\Request;

class OperationLogController extends Controller
{
    public function index(Request $request)
    {
        $query = OperationLog::with(['system', 'performedBy']);

        if ($systemId = $request->query('system')) {
            $query->where('system_id', $systemId);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        $logs = $query->orderByDesc('performed_at')->paginate(20);
        $systems = System::orderBy('name')->get();

        return view('admin.logs.index', [
            'logs' => $logs,
            'systems' => $systems,
            'filters' => $request->only(['system', 'type']),
        ]);
    }

    public function store(Request $request, System $system)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:backup,maintenance,issue,other'],
            'title' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:10000'],
            'performed_at' => ['required', 'date'],
        ]);

        $validated['performed_by_id'] = $request->user()->id;
        $system->operationLogs()->create($validated);

        return back()->with('success', 'Operation log added.');
    }

    public function destroy(System $system, OperationLog $log)
    {
        $log->delete();

        return back()->with('success', 'Operation log deleted.');
    }
}
