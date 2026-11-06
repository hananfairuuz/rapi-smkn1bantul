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
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
        <div class="calendar-legend" style="margin: 0;" aria-label="Warna agenda berdasarkan penyelenggara">
            <span><i class="calendar-legend-color guru"></i>Guru</span>
            <span><i class="calendar-legend-color organisasi"></i>Organisasi</span>
            <span><i class="calendar-legend-color admin"></i>Admin</span>
            <span style="color: #dc2626; font-weight: 600;"><i class="calendar-legend-color" style="background: #dc2626;"></i>Libur Nasional</span>
        </div>

        <form method="GET" action="{{ route('calendar') }}" class="calendar-search-form" style="display: flex; gap: 8px; max-width: 400px; width: 100%;">
            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari acara atau ruangan..." class="form-control" style="flex: 1; padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 13px;">
            <button type="submit" class="btn primary" style="white-space: nowrap; padding: 7px 14px; font-size: 13px;">Cari</button>
            @if(!empty($search))
                <a href="{{ route('calendar', ['month' => today()->format('Y-m')]) }}" class="btn secondary" style="padding: 7px 12px; font-size: 13px;">Reset</a>
            @endif
        </form>
    </div>

    @if(!empty($search))
        @if(!empty($targetDate))
            <div style="margin: 0 0 16px; padding: 10px 14px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; color: #065f46; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; font-size: 13px;">
                <span>🎯 Ditemukan untuk "<strong>{{ $search }}</strong>" pada tanggal <strong>{{ \Carbon\Carbon::parse($targetDate)->locale('id')->translatedFormat('d F Y') }}</strong> (Kalender otomatis diarahkan ke bulan ini).</span>
                <a href="{{ route('calendar', ['month' => today()->format('Y-m')]) }}" style="color: #047857; font-weight: 700; text-decoration: underline;">Kembali ke Hari Ini</a>
            </div>
        @else
            <div style="margin: 0 0 16px; padding: 10px 14px; background: #fffbe8; border: 1px solid #fde047; border-radius: 8px; color: #854d0e; font-size: 13px;">
                Tidak ditemukan acara atau hari libur dengan kata kunci "<strong>{{ $search }}</strong>".
            </div>
        @endif
    @endif

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
                <div class="calendar-day {{ $calendarDate->isToday() ? 'today' : '' }} {{ $isWeekend ? 'weekend' : '' }} {{ isset($holidays[$dateKey]) ? 'holiday' : '' }} {{ !empty($targetDate) && $targetDate === $dateKey ? 'search-matched' : '' }}" @if(isset($holidays[$dateKey])) title="{{ $holidays[$dateKey] }}" @endif>
                    <span class="day-number">{{ $date }}</span>
                    @if(isset($holidays[$dateKey]))
                        <div class="calendar-event calendar-event--holiday" title="{{ $holidays[$dateKey] }} (Hari Libur Nasional)">
                            <strong>{{ $holidays[$dateKey] }}</strong>
                            <span>Libur Nasional</span>
                        </div>
                    @endif
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
