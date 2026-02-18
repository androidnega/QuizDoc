@extends('layouts.student-dashboard')

@section('title', 'Project')
@section('dashboard_heading', 'Project')

@section('dashboard_content')
    {{-- Flash (success/error) shown once via layouts.app flash popup --}}
    @yield('content')
@endsection
