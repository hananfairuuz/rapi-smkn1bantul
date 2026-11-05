<?php

namespace App\Support;

final class LessonSchedule
{
    public const FIRST_START_MINUTE = 7 * 60 + 15;

    public const DURATION_MINUTES = 45;

    public const LESSON_COUNT = 14;

    public static function periods(): array
    {
        $periods = [];

        for ($lesson = 1; $lesson <= self::LESSON_COUNT; $lesson++) {
            $start = self::FIRST_START_MINUTE + ($lesson - 1) * self::DURATION_MINUTES;
            $end = $start + self::DURATION_MINUTES;
            $periods[$lesson] = [
                'start' => self::formatTime($start),
                'end' => self::formatTime($end),
            ];
        }

        return $periods;
    }

    public static function interval(int $startLesson, int $endLesson): array
    {
        $periods = self::periods();

        return [
            $periods[$startLesson]['start'],
            $periods[$endLesson]['end'],
        ];
    }

    public static function lessonStartingAt(string $time): ?int
    {
        foreach (self::periods() as $lesson => $period) {
            if ($period['start'] === $time) {
                return $lesson;
            }
        }

        return null;
    }

    public static function lessonEndingAt(string $time): ?int
    {
        foreach (self::periods() as $lesson => $period) {
            if ($period['end'] === $time) {
                return $lesson;
            }
        }

        return null;
    }

    private static function formatTime(int $minuteOfDay): string
    {
        return sprintf('%02d:%02d', intdiv($minuteOfDay, 60), $minuteOfDay % 60);
    }
}
