@extends('layouts.examiner')

@section('title', 'Docu Mentor – Supervisor')

@section('examiner_heading', 'Docu Mentor')

@section('examiner_content')
    {{-- Flash (success/error) shown once via layouts.app toast --}}
    <div class="mt-2 md:mt-4">
        @yield('content')
    </div>
@endsection
