<?php

namespace App\Documents;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * The browser app files daily orders and bills under one document per route and day,
 * keyed "YYYY-MM-DD_routeKey".
 */
class DocumentKey
{
    public static function make(CarbonInterface $date, string $routeKey): string
    {
        return $date->format('Y-m-d').'_'.$routeKey;
    }

    /**
     * @return array{0: string, 1: string}|null The date and the route key.
     */
    public static function parse(string $key): ?array
    {
        if (! preg_match('/^(\d{4}-\d{2}-\d{2})_(.+)$/', $key, $matches)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $matches[1]));

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return [$matches[1], $matches[2]];
    }

    /**
     * Convert a browser timestamp in milliseconds to a date, if one was given.
     */
    public static function timestamp(mixed $milliseconds): ?Carbon
    {
        return is_numeric($milliseconds)
            ? Carbon::createFromTimestampMs((float) $milliseconds, date_default_timezone_get())
            : null;
    }

    /**
     * Convert a date to a browser timestamp in milliseconds.
     */
    public static function milliseconds(?CarbonInterface $date): ?int
    {
        return $date?->getTimestampMs();
    }
}
