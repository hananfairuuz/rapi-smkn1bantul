<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Services\IndonesiaHolidayCalendar;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

class CalendarController extends Controller
{
    public function index(Request $request, IndonesiaHolidayCalendar $holidayCalendar)
    {
        $month = $request->filled('month') ? Carbon::createFromFormat('Y-m', $request->string('month')->toString())->startOfMonth() : today()->startOfMonth();
        $search = $request->string('search')->trim()->toString();
        $targetDate = null;

        // Jika melakukan pencarian, otomatis cari tanggal libur atau agenda yang cocok
        if ($search !== '') {
            $searchLower = strtolower($search);
            $baseYear = (int) $month->year;

            // 1. Periksa apakah cocok dengan Hari Libur Nasional (cek tahun ini, tahun depan, tahun lalu)
            foreach ([$baseYear, $baseYear + 1, $baseYear - 1] as $yr) {
                try {
                    $yrHolidays = $holidayCalendar->holidaysForYear($yr);
                } catch (\Throwable) {
                    $yrHolidays = $holidayCalendar->getBuiltinHolidaysForYear($yr);
                }
                foreach ($yrHolidays as $dateStr => $holidayTitle) {
                    if (str_contains(strtolower($holidayTitle), $searchLower)) {
                        $targetDate = $dateStr;
                        break 2;
                    }
                }
            }

            // 2. Periksa apakah cocok dengan Agenda Ruangan yang disetujui
            $matchedEvent = Event::with(['room', 'user.role'])
                ->where('status', 'approved')
                ->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('room', function ($rq) use ($search) {
                            $rq->where('name', 'like', "%{$search}%")
                                ->orWhere('location', 'like', "%{$search}%");
                        });
                })
                ->orderBy('date')
                ->first();

            if ($matchedEvent !== null && ($targetDate === null || $matchedEvent->date->toDateString() < $targetDate)) {
                $targetDate = $matchedEvent->date->toDateString();
            }

            // Otomatis arahkan bulan kalender ke bulan dari acara/libur yang dicari!
            if ($targetDate !== null) {
                $month = Carbon::parse($targetDate)->startOfMonth();
            }
        }

        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        $eventsQuery = Event::with(['room', 'user.role'])
            ->where('status', 'approved')
            ->whereDate('date', '<=', $monthEnd)
            ->where(function ($query) use ($monthStart) {
                $query->whereDate('end_date', '>=', $monthStart)
                    ->orWhere(function ($query) use ($monthStart) {
                        $query->whereNull('end_date')
                            ->whereDate('date', '>=', $monthStart);
                    })
                    ->orWhere(function ($query) use ($monthStart) {
                        $query->whereNull('end_date')
                            ->whereColumn('end_time', '<', 'start_time')
                            ->whereDate('date', '>=', $monthStart->copy()->subDay());
                    });
            });

        $events = $eventsQuery->orderBy('date')->orderBy('start_time')->get();
        $holidaysAvailable = true;

        try {
            $holidays = $holidayCalendar->holidaysForYear((int) $month->year);
        } catch (\Throwable $exception) {
            $holidays = $holidayCalendar->getBuiltinHolidaysForYear((int) $month->year);
        }

        return view($request->user() ? 'calendar' : 'calendar-public', compact('month', 'events', 'holidays', 'holidaysAvailable', 'search', 'targetDate'));
    }

    public function about()
    {
        return view('pages.about');
    }

    public function services()
    {
        return view('pages.services');
    }

    public function guide()
    {
        return view('pages.guide');
    }
}
