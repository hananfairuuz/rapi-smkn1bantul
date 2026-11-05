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
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $events = Event::with(['room', 'user.role'])
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
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
        $holidaysAvailable = true;

        try {
            $holidays = $holidayCalendar->holidaysForYear((int) $month->year);
        } catch (ConnectionException|RequestException|UnexpectedValueException $exception) {
            Log::warning('Unable to load Indonesia public holiday calendar.', ['exception' => $exception]);
            $holidays = [];
            $holidaysAvailable = false;
        }

        return view($request->user() ? 'calendar' : 'calendar-public', compact('month', 'events', 'holidays', 'holidaysAvailable'));
    }
}
