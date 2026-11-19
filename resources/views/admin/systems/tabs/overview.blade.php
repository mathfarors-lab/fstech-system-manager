<div class="row g-3">
    <div class="col-md-6">
        <h6 class="fw-bold text-body-secondary text-uppercase" style="font-size:.72rem;letter-spacing:.06em">Overview</h6>
        <div class="d-flex justify-content-between border-bottom py-2 small"><span class="text-body-secondary">Latest version</span><strong>{{ $system->latestVersion?->version ?? '—' }}</strong></div>
        <div class="d-flex justify-content-between border-bottom py-2 small"><span class="text-body-secondary">Owner</span><strong>{{ $system->owner?->name ?? '—' }}</strong></div>
        <div class="d-flex justify-content-between border-bottom py-2 small"><span class="text-body-secondary">Created</span><strong>{{ $system->created_at->format('M j, Y') }}</strong></div>
        <div class="d-flex justify-content-between py-2 small"><span class="text-body-secondary">Description</span><span class="text-end" style="max-width:60%">{{ $system->description ?? '—' }}</span></div>
    </div>
    <div class="col-md-6">
        <div class="row g-2 mb-3">
            <div class="col-4"><div class="fst-card p-3 text-center"><div class="fs-4 fw-bold">{{ $versionsCount }}</div><small class="text-body-secondary">Versions</small></div></div>
            <div class="col-4"><div class="fst-card p-3 text-center"><div class="fs-4 fw-bold">{{ $buildsCount }}</div><small class="text-body-secondary">Builds</small></div></div>
            <div class="col-4"><div class="fst-card p-3 text-center"><div class="fs-4 fw-bold">{{ $openTasksCount }}</div><small class="text-body-secondary">Open tasks</small></div></div>
        </div>

        <h6 class="fw-bold text-body-secondary text-uppercase" style="font-size:.72rem;letter-spacing:.06em">Recent builds</h6>
        @forelse($recentBuilds as $build)
            <div class="d-flex align-items-center gap-2 py-1 small {{ !$loop->first ? 'border-top' : '' }}">
                <x-status-pill :status="$build->status" />
                <span>{{ $build->version_label }} → {{ \App\Models\System::ENVIRONMENTS[$build->environment] ?? $build->environment }}</span>
                <span class="ms-auto text-body-secondary">{{ $build->built_at?->diffForHumans() ?? 'not started' }}</span>
            </div>
        @empty
            <p class="small text-body-secondary mb-0">No builds yet</p>
        @endforelse

        <h6 class="fw-bold text-body-secondary text-uppercase mt-3" style="font-size:.72rem;letter-spacing:.06em">Open tasks</h6>
        @forelse($openTasks as $task)
            <div class="d-flex align-items-center gap-2 py-1 small {{ !$loop->first ? 'border-top' : '' }}">
                <x-status-pill :status="$task->priority" />
                <span>{{ $task->title }}</span>
                <span class="ms-auto text-body-secondary">{{ $task->assignee?->name ?? 'Unassigned' }}</span>
            </div>
        @empty
            <p class="small text-body-secondary mb-0">No open tasks</p>
        @endforelse
    </div>
</div>
