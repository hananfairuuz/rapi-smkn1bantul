<div class="page-heading calendar-heading">
    <div>
        <h1>Kalender Agenda</h1>
        <p>Agenda ruangan yang telah disetujui.</p>
    </div>
</div>

<section class="card calendar-card">
    @unless($holidaysAvailable)
        <div class="alert error-alert" role="status">Data hari libur nasional tidak dapat dimuat. Periksa koneksi internet, lalu muat ulang kalender.</div>
    @endunless
    <div class="month-nav">
        <a class="btn secondary" href="{{ route('calendar', ['month' => $month->copy()->subMonth()->format('Y-m')]) }}">&larr; Sebelumnya</a>
        <h2>{{ $month->copy()->locale('id')->translatedFormat('F Y') }}</h2>
        <div class="month-nav-next">
            <a class="btn secondary" href="{{ route('calendar', ['month' => today()->format('Y-m')]) }}">Hari ini</a>
            <a class="btn secondary" href="{{ route('calendar', ['month' => $month->copy()->addMonth()->format('Y-m')]) }}">Berikutnya &rarr;</a>
        </div>
        <div class="calendar-legend" aria-label="Warna agenda berdasarkan penyelenggara">
            <span><i class="calendar-legend-color guru"></i>Guru</span>
            <span><i class="calendar-legend-color organisasi"></i>Organisasi</span>
            <span><i class="calendar-legend-color admin"></i>Admin</span>
        </div>
    </div>

    <div class="calendar-scroll">
        <div class="calendar-grid">
            @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $index => $day)
                <div class="calendar-weekday {{ $index >= 5 ? 'weekend' : '' }}">{{ $day }}</div>
            @endforeach

            @php($start = $month->copy()->startOfMonth()->dayOfWeekIso)
            @for($blank = 1; $blank < $start; $blank++)
                <div class="calendar-day empty"></div>
            @endfor

            @for($date = 1; $date <= $month->daysInMonth; $date++)
                @php($calendarDate = $month->copy()->day($date))
                @php($dayEvents = $events->filter(fn ($event) => $event->date->toDateString() <= $calendarDate->toDateString() && $event->endAt()->toDateString() >= $calendarDate->toDateString()))
                @php($dateKey = $calendarDate->toDateString())
                @php($isWeekend = $calendarDate->isWeekend())
                <div class="calendar-day {{ $calendarDate->isToday() ? 'today' : '' }} {{ $isWeekend ? 'weekend' : '' }} {{ isset($holidays[$dateKey]) ? 'holiday' : '' }}" @if(isset($holidays[$dateKey])) title="{{ $holidays[$dateKey] }}" @endif>
                    <span class="day-number">{{ $date }}</span>
                    @foreach($dayEvents as $event)
                        @php($eventDayTime = $event->isMultiDay() ? ($calendarDate->isSameDay($event->date) ? 'Mulai '.substr($event->start_time, 0, 5) : ($calendarDate->isSameDay($event->end_date) ? 'Selesai '.substr($event->end_time, 0, 5) : 'Berlangsung')) : substr($event->start_time, 0, 5).'–'.substr($event->end_time, 0, 5).($event->endsNextDay() ? ' (+1 hari)' : ''))
                        @php($organizerRole = $event->user->role?->name ?? 'other')
                        <div class="calendar-event calendar-event--{{ in_array($organizerRole, ['guru', 'organisasi', 'admin'], true) ? $organizerRole : 'other' }}" title="{{ $event->title }} - {{ $eventDayTime }} - {{ $event->room->name }}">
                            <strong>{{ $event->title }}</strong>
                            <span>{{ $eventDayTime }}</span>
                            <span>{{ $event->room->name }}</span>
                        </div>
                    @endforeach
                </div>
            @endfor
        </div>
    </div>
</section>
