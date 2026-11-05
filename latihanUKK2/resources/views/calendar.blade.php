@extends('layouts.app')

@section('title', 'Kalender · RAPI-Sekolah')
@section('topbar', 'Kalender Agenda Ruangan')

@section('content')
    <div class="calendar-watermark">
    @include('calendar-grid')
    </div>
@endsection
