{{-- resources/views/admin/admin_dashboard.blade.php --}}

@extends('layouts.base')

@section('title', 'SIIIMS | Admin Dashboard')

@push('styles')
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/pages/inmates.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/pages/add-inmate.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/pages/add-cell.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/pages/cells-drawer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/navbars/topbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/ai/ai-assistant.css') }}">
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
        @include('admin.overview.overview')
        @include('admin.ai.ai_assistant')
        @include('admin.management.inmate.add-inmate')
        @include('admin.management.inmate.inmates')
        @include('admin.management.cell.add-cell')
        @include('admin.management.cell.cells')
        @include('admin.management.inmate.incidents')
        @include('admin.management.inmate.schedules')
        @include('admin.management.cell.add-cell')
        @include('admin.management.cell.cells')
        @include('admin.management.staff.staff-list')
        @include('admin.management.staff.staff-assignment')
        @include('admin.accounts.users')
        @include('admin.system.logs')
        @include('admin.system.analytics')
        @include('admin.personal.profile')
        @include('admin.contacts.emergency-contacts')

    </main>
</div>
@endsection

@push('scripts')
    <script>
        const inmates = @json($inmates_json);
    </script>
    <script>
        window.AI_ASSISTANT_CONFIG = {
            chatUrl: "{{ route('ai-assistant.chat') }}",
            modelsUrl: "{{ route('ai-assistant.models.index') }}",
            setModelUrl: "{{ route('ai-assistant.models.set') }}",
            csrfToken: "{{ csrf_token() }}",
        };
    </script>
    <script src="{{ asset('js/admin/dashboard.js') }}" defer></script>
    <script src="{{ asset('js/admin/pages/inmates.js') }}" defer></script>
    <script src="{{ asset('js/admin/pages/add-inmate.js') }}" defer></script>
    <script src="{{ asset('js/admin/pages/add-cell.js') }}" defer></script>
    <script src="{{ asset('js/admin/pages/cells.js') }}" defer></script>
    <script src="{{ asset('js/admin/ai/ai-assistant.js') }}" defer></script>
@endpush