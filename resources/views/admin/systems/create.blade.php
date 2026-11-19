@extends('layouts.app')

@section('title', 'New System')

@section('content')
<div class="mb-4">
    <a href="{{ route('systems.index') }}" class="link-plain small"><i class="bi bi-arrow-left me-1"></i>Back to systems</a>
    <h4 class="fw-bold mb-0 mt-1">New System</h4>
</div>

<div class="fst-card p-4">
    @include('admin.systems._form')
</div>
@endsection
