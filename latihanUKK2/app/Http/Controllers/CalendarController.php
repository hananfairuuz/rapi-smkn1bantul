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
        $search = $request->string('search')->trim()->toString();

        $eventsQuery = Event::with(['room', 'user.role'])
            ->where('status', 'approved');

        if ($search !== '') {
            $eventsQuery->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('room', function ($rq) use ($search) {
                        $rq->where('name', 'like', "%{$search}%")
                            ->orWhere('location', 'like', "%{$search}%");
                    });
            });
        } else {
            $eventsQuery->whereDate('date', '<=', $monthEnd)
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
        }

        $events = $eventsQuery->orderBy('date')->orderBy('start_time')->get();
        $holidaysAvailable = true;

        try {
            $holidays = $holidayCalendar->holidaysForYear((int) $month->year);
        } catch (\Throwable $exception) {
            $holidays = $holidayCalendar->getBuiltinHolidaysForYear((int) $month->year);
        }

        return view($request->user() ? 'calendar' : 'calendar-public', compact('month', 'events', 'holidays', 'holidaysAvailable', 'search'));
    }
}
