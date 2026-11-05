<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class IndonesiaHolidayCalendar
{
    /**
     * @return array<string, string>
     */
    public function holidaysForYear(int $year): array
    {
        return Cache::remember("indonesia_public_holidays_{$year}", now()->addDay(), function () use ($year): array {
            $response = Http::accept('text/calendar')
                ->timeout(10)
                ->get('https://calendar.google.com/calendar/ical/id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics')
                ->throw();
            $calendar = $response->body();

            if (! str_contains($calendar, 'BEGIN:VCALENDAR') || ! str_contains($calendar, 'END:VCALENDAR')) {
                throw new UnexpectedValueException('The Indonesia public holiday calendar response is invalid.');
            }

            preg_match_all('/BEGIN:VEVENT\s*(.*?)\s*END:VEVENT/s', $calendar, $matches);
            $holidays = [];

            foreach ($matches[1] as $event) {
                if (! preg_match('/^DESCRIPTION:(.*?)\r?$/m', $event, $description)
                    || ! str_contains(strtolower($description[1]), 'hari libur nasional')
                    || ! preg_match('/^DTSTART(?:;VALUE=DATE)?:([0-9]{8})\r?$/m', $event, $startDate)) {
                    continue;
                }

                $date = $startDate[1];
                if ((int) substr($date, 0, 4) !== $year) {
                    continue;
                }

                preg_match('/^SUMMARY:(.*?)\r?$/m', $event, $summary);
                $holidays[substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2)] = $summary[1] ?? 'Hari libur nasional';
            }

            return $holidays;
        });
    }
}
