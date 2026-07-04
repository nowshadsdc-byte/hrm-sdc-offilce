@extends('tyro-dashboard::layouts.admin')

@section('title', 'Attendance Settings')

@push('styles')
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
    @inertiaHead
@endpush

@section('content')
    @inertia
@endsection