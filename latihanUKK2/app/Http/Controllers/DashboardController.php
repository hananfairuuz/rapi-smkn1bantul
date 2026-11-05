<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use App\Models\SystemNotification;
use Carbon\CarbonImmutable;

class DashboardController extends Controller
{
    public function index()
    {
        $now = now('Asia/Jakarta');
        $todayEvents = $this->eventsOnDate($now->toDateString());
        $ongoingEvents = (clone $todayEvents)->get()
            ->filter(fn (Event $event) => $event->endAt()->gt($now)
                && CarbonImmutable::parse($event->date->toDateString().' '.$event->start_time, 'Asia/Jakarta')->lte($now))
            ->count();

        return view('dashboard', [
            'totalRooms' => Room::count(),
            'roomsInUse' => (clone $todayEvents)->whereIn('status', ['pending', 'approved'])->distinct('room_id')->count('room_id'),
            'todayEvents' => $todayEvents->count(),
            'ongoingEvents' => $ongoingEvents,
            'pendingComplaints' => Complaint::where('status_repair', 'Belum Diperbaiki')->count(),
            'recentEvents' => Event::with(['room', 'user'])->orderBy('date')->orderBy('start_time')->take(5)->get(),
            'notifications' => SystemNotification::where('user_id', auth()->id())->whereNull('read_at')->latest()->get(),
        ]);
    }

    private function eventsOnDate(string $date)
    {
        $previousDate = CarbonImmutable::parse($date)->subDay()->toDateString();

        return Event::whereDate('date', '<=', $date)
            ->where(function ($query) use ($date, $previousDate) {
                $query->whereDate('end_date', '>=', $date)
                    ->orWhere(function ($query) use ($date) {
                        $query->whereNull('end_date')->whereDate('date', '>=', $date);
                    })
                    ->orWhere(function ($query) use ($previousDate) {
                        $query->whereNull('end_date')
                            ->whereColumn('end_time', '<', 'start_time')
                            ->whereDate('date', $previousDate);
                    });
            });
    }
}
