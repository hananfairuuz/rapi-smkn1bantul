@extends('layouts.app')

@section('title', 'Pesan WhatsApp · RAPI-Sekolah')
@section('topbar', 'Pesan WhatsApp Pemohon')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Tulis Pesan WhatsApp</h1>
            <p>Isi pesan secara manual. Setelah menekan tombol, WhatsApp akan dibuka dan Anda perlu mengirim pesannya dari sana.</p>
        </div>
    </div>

    <form class="card form-card" method="post" action="{{ route('events.whatsapp.send', $event) }}" target="_blank">
        @csrf
        <p><strong>Penerima:</strong> {{ $event->borrower_name ?: $event->user->name }} · {{ $event->borrower_whatsapp }}</p>
        <p><strong>Agenda:</strong> {{ $event->title }} · {{ $event->room->name }} · {{ $event->date->format('d M Y') }}@if($event->isMultiDay()) – {{ $event->end_date->format('d M Y') }}@endif, {{ substr($event->start_time, 0, 5) }}–{{ substr($event->end_time, 0, 5) }}{{ $event->endsNextDay() && ! $event->isMultiDay() ? ' (+1 hari)' : '' }}</p>
        <label for="message">Isi pesan</label>
        <textarea id="message" name="message" rows="7" maxlength="5000" required>{{ old('message') }}</textarea>
        <div class="actions">
            <button type="submit">Buka WhatsApp</button>
            <a class="btn secondary" href="{{ route('events.index') }}">Batal</a>
        </div>
    </form>
@endsection
