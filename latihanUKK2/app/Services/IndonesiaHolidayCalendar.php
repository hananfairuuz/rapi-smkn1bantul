<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class IndonesiaHolidayCalendar
{
    /**
     * Daftar hari libur nasional dan cuti bersama Indonesia resmi.
     * Digunakan sebagai data offline dan cadangan handal saat jaringan lambat atau tidak tersedia.
     */
    protected static array $builtinHolidays = [
        // 2024
        '2024-01-01' => 'Tahun Baru 2024 Masehi',
        '2024-02-08' => 'Isra Mikraj Nabi Muhammad SAW',
        '2024-02-09' => 'Cuti Bersama Tahun Baru Imlek',
        '2024-02-10' => 'Tahun Baru Imlek 2575 Kongzili',
        '2024-03-11' => 'Hari Suci Nyepi Tahun Baru Saka 1946',
        '2024-03-12' => 'Cuti Bersama Hari Suci Nyepi',
        '2024-03-29' => 'Wafat Yesus Kristus',
        '2024-03-31' => 'Hari Paskah',
        '2024-04-08' => 'Cuti Bersama Idul Fitri 1445 H',
        '2024-04-09' => 'Cuti Bersama Idul Fitri 1445 H',
        '2024-04-10' => 'Hari Raya Idul Fitri 1445 H',
        '2024-04-11' => 'Hari Raya Idul Fitri 1445 H',
        '2024-04-12' => 'Cuti Bersama Idul Fitri 1445 H',
        '2024-04-15' => 'Cuti Bersama Idul Fitri 1445 H',
        '2024-05-01' => 'Hari Buruh Internasional',
        '2024-05-09' => 'Kenaikan Yesus Kristus',
        '2024-05-10' => 'Cuti Bersama Kenaikan Yesus Kristus',
        '2024-05-23' => 'Hari Raya Waisak 2568 BE',
        '2024-05-24' => 'Cuti Bersama Hari Raya Waisak',
        '2024-06-01' => 'Hari Lahir Pancasila',
        '2024-06-17' => 'Hari Raya Idul Adha 1445 H',
        '2024-06-18' => 'Cuti Bersama Hari Raya Idul Adha',
        '2024-07-07' => 'Tahun Baru Islam 1446 H',
        '2024-08-17' => 'Hari Proklamasi Kemerdekaan RI',
        '2024-09-16' => 'Maulid Nabi Muhammad SAW',
        '2024-12-24' => 'Cuti Bersama Hari Raya Natal',
        '2024-12-25' => 'Hari Raya Natal',
        '2024-12-26' => 'Cuti Bersama Hari Raya Natal',

        // 2025
        '2025-01-01' => 'Tahun Baru 2025 Masehi',
        '2025-01-27' => 'Isra Mikraj Nabi Muhammad SAW',
        '2025-01-28' => 'Cuti Bersama Tahun Baru Imlek',
        '2025-01-29' => 'Tahun Baru Imlek 2576 Kongzili',
        '2025-03-28' => 'Cuti Bersama Hari Suci Nyepi',
        '2025-03-29' => 'Hari Suci Nyepi Tahun Baru Saka 1947',
        '2025-03-31' => 'Hari Raya Idul Fitri 1446 H',
        '2025-04-01' => 'Hari Raya Idul Fitri 1446 H',
        '2025-04-02' => 'Cuti Bersama Idul Fitri 1446 H',
        '2025-04-03' => 'Cuti Bersama Idul Fitri 1446 H',
        '2025-04-04' => 'Cuti Bersama Idul Fitri 1446 H',
        '2025-04-07' => 'Cuti Bersama Idul Fitri 1446 H',
        '2025-04-18' => 'Wafat Yesus Kristus',
        '2025-04-20' => 'Hari Paskah',
        '2025-05-01' => 'Hari Buruh Internasional',
        '2025-05-12' => 'Hari Raya Waisak 2569 BE',
        '2025-05-13' => 'Cuti Bersama Hari Raya Waisak',
        '2025-05-29' => 'Kenaikan Yesus Kristus',
        '2025-05-30' => 'Cuti Bersama Kenaikan Yesus Kristus',
        '2025-06-01' => 'Hari Lahir Pancasila',
        '2025-06-06' => 'Hari Raya Idul Adha 1446 H',
        '2025-06-09' => 'Cuti Bersama Hari Raya Idul Adha',
        '2025-06-27' => 'Tahun Baru Islam 1447 H',
        '2025-08-17' => 'Hari Proklamasi Kemerdekaan RI',
        '2025-09-05' => 'Maulid Nabi Muhammad SAW',
        '2025-12-24' => 'Cuti Bersama Hari Raya Natal',
        '2025-12-25' => 'Hari Raya Natal',
        '2025-12-26' => 'Cuti Bersama Hari Raya Natal',

        // 2026
        '2026-01-01' => 'Tahun Baru 2026 Masehi',
        '2026-01-16' => 'Isra Mikraj Nabi Muhammad SAW',
        '2026-02-16' => 'Cuti Bersama Tahun Baru Imlek',
        '2026-02-17' => 'Tahun Baru Imlek 2577 Kongzili',
        '2026-03-19' => 'Hari Suci Nyepi Tahun Baru Saka 1948',
        '2026-03-20' => 'Hari Raya Idul Fitri 1447 H',
        '2026-03-21' => 'Hari Raya Idul Fitri 1447 H',
        '2026-03-23' => 'Cuti Bersama Idul Fitri 1447 H',
        '2026-03-24' => 'Cuti Bersama Idul Fitri 1447 H',
        '2026-04-03' => 'Wafat Yesus Kristus',
        '2026-04-05' => 'Hari Paskah',
        '2026-05-01' => 'Hari Buruh Internasional',
        '2026-05-14' => 'Kenaikan Yesus Kristus',
        '2026-05-27' => 'Hari Raya Idul Adha 1447 H',
        '2026-05-31' => 'Hari Raya Waisak 2570 BE',
        '2026-06-01' => 'Hari Lahir Pancasila',
        '2026-06-16' => 'Tahun Baru Islam 1448 H',
        '2026-08-17' => 'Hari Proklamasi Kemerdekaan RI',
        '2026-08-25' => 'Maulid Nabi Muhammad SAW',
        '2026-12-24' => 'Cuti Bersama Hari Raya Natal',
        '2026-12-25' => 'Hari Raya Natal',
        '2026-12-26' => 'Cuti Bersama Hari Raya Natal',

        // 2027
        '2027-01-01' => 'Tahun Baru 2027 Masehi',
        '2027-01-06' => 'Isra Mikraj Nabi Muhammad SAW',
        '2027-02-06' => 'Tahun Baru Imlek 2578 Kongzili',
        '2027-03-09' => 'Hari Raya Idul Fitri 1448 H',
        '2027-03-10' => 'Hari Raya Idul Fitri 1448 H',
        '2027-03-11' => 'Cuti Bersama Idul Fitri',
        '2027-03-12' => 'Cuti Bersama Idul Fitri',
        '2027-03-26' => 'Wafat Yesus Kristus',
        '2027-03-28' => 'Hari Paskah',
        '2027-04-07' => 'Hari Suci Nyepi Tahun Baru Saka 1949',
        '2027-05-01' => 'Hari Buruh Internasional',
        '2027-05-06' => 'Kenaikan Yesus Kristus',
        '2027-05-17' => 'Hari Raya Idul Adha 1448 H',
        '2027-05-20' => 'Hari Raya Waisak 2571 BE',
        '2027-06-01' => 'Hari Lahir Pancasila',
        '2027-06-06' => 'Tahun Baru Islam 1449 H',
        '2027-08-15' => 'Maulid Nabi Muhammad SAW',
        '2027-08-17' => 'Hari Proklamasi Kemerdekaan RI',
        '2027-12-24' => 'Cuti Bersama Hari Raya Natal',
        '2027-12-25' => 'Hari Raya Natal',
    ];

    /**
     * @return array<string, string>
     */
    public function holidaysForYear(int $year): array
    {
        try {
            return Cache::remember("indonesia_public_holidays_{$year}", now()->addDays(7), function () use ($year): array {
                return $this->resolveHolidays($year);
            });
        } catch (Throwable) {
            return $this->resolveHolidays($year);
        }
    }

    /**
     * Mengambil dan menggabungkan hari libur dari Google iCal dan data bawaan.
     * @return array<string, string>
     */
    protected function resolveHolidays(int $year): array
    {
        $holidays = $this->getBuiltinHolidaysForYear($year);

        // Coba fetch dari Google Calendar iCal jika online
        try {
            $response = Http::accept('text/calendar')
                ->timeout(3)
                ->get('https://calendar.google.com/calendar/ical/id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics');

            if ($response->successful()) {
                $calendar = $response->body();
                if (str_contains($calendar, 'BEGIN:VCALENDAR') && str_contains($calendar, 'END:VCALENDAR')) {
                    preg_match_all('/BEGIN:VEVENT\s*(.*?)\s*END:VEVENT/s', $calendar, $matches);
                    foreach ($matches[1] as $event) {
                        if (! preg_match('/^DTSTART(?:;VALUE=DATE)?:([0-9]{8})\r?$/m', $event, $startDate)) {
                            continue;
                        }
                        $date = $startDate[1];
                        if ((int) substr($date, 0, 4) !== $year) {
                            continue;
                        }

                        $desc = '';
                        if (preg_match('/^DESCRIPTION:(.*?)\r?$/m', $event, $description)) {
                            $desc = strtolower($description[1]);
                        }

                        // Google labels include "Hari libur nasional", "Cuti bersama", "Hari libur bersama"
                        if ($desc !== '' && ! str_contains($desc, 'hari libur') && ! str_contains($desc, 'cuti')) {
                            continue;
                        }

                        preg_match('/^SUMMARY:(.*?)\r?$/m', $event, $summary);
                        $dateKey = substr($date, 0, 4).'-'.substr($date, 4, 2).'-'.substr($date, 6, 2);
                        $holidays[$dateKey] = $summary[1] ?? 'Hari Libur Nasional';
                    }
                }
            }
        } catch (Throwable $e) {
            // Jaringan offline atau timeout, data builtin tetap digunakan
            Log::info('Fetch Google Calendar holiday skipped or timed out, using built-in holidays: ' . $e->getMessage());
        }

        ksort($holidays);
        return $holidays;
    }

    /**
     * Ambil hari libur bawaan untuk tahun tertentu.
     * @return array<string, string>
     */
    public function getBuiltinHolidaysForYear(int $year): array
    {
        $result = [];
        $prefix = (string) $year . '-';

        foreach (self::$builtinHolidays as $date => $name) {
            if (str_starts_with($date, $prefix)) {
                $result[$date] = $name;
            }
        }

        // Jika tahun di luar rentang, berikan setidaknya libur tanggal tetap
        if (empty($result)) {
            $result["{$year}-01-01"] = 'Tahun Baru ' . $year . ' Masehi';
            $result["{$year}-05-01"] = 'Hari Buruh Internasional';
            $result["{$year}-06-01"] = 'Hari Lahir Pancasila';
            $result["{$year}-08-17"] = 'Hari Kemerdekaan RI';
            $result["{$year}-12-25"] = 'Hari Raya Natal';
            $result["{$year}-12-26"] = 'Cuti Bersama Hari Raya Natal';
        }

        return $result;
    }
}
