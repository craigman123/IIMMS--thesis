{{-- resources/views/admin/admin_dashboard.blade.php --}}

@extends('layouts.base')

@section('title', 'IIMMS | Admin Dashboard')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
@endpush

@section('content')
<div class="dashboard-root">

    {{-- ── SIDEBAR ── --}}
    @include('admin.partials.sidebar')

    {{-- ── MAIN CONTENT ── --}}
    <main class="main-content" id="mainContent">

        {{-- ── TOP BAR ── --}}
        @include('admin.partials.topbar')

        {{-- ── PAGES ── --}}
        @include('admin.pages.overview')
        @include('admin.pages.add-inmate')
        @include('admin.pages.inmates')
        @include('admin.pages.cells')
        @include('admin.pages.incidents')
        @include('admin.pages.releases')
        @include('admin.pages.users')
        @include('admin.pages.logs')

    </main>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/admin/dashboard.js') }}" defer></script>
@endpush