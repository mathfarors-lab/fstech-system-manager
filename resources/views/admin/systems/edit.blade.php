@extends('layouts.app')

@section('title', 'Edit '.$system->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('systems.show', $system) }}" class="link-plain small"><i class="bi bi-arrow-left me-1"></i>Back to {{ $system->name }}</a>
    <h4 class="fw-bold mb-0 mt-1">Edit System</h4>
</div>

<div class="fst-card p-4">
    @include('admin.systems._form')
</div>
@endsection
