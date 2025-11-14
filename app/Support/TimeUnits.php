<?php

namespace App\Support;

use InvalidArgumentException;

class TimeUnits
{
    /**
     * Parse a human-readable duration into seconds.
     * Supports combinations like "1d 2h 30m", and units up to millennia.
     * Units:
     *  s, sec, second(s)
     *  m, min, minute(s)
     *  h, hr, hour(s)
     *  d, day(s)
     *  w, week(s) (7d)
     *  mo, mon, month(s) (30d fixed)
     *  y, yr, year(s) (365d fixed)
     *  dec, decade(s) (10y)
     *  cen, century/centuries (100y)
     *  mil, millennium/millennia (1000y)
     */
    public static function parseToSeconds(string|int|float $input): int
    {
        if (is_int($input)) return max(0, $input);
        if (is_float($input)) return max(0, (int) floor($input));

        $str = trim(strtolower((string) $input));
        if ($str === '') throw new InvalidArgumentException('Empty duration');
        $str = preg_replace('/[,]+/', '', $str);

        // If it's purely numeric, assume seconds
        if (preg_match('/^[-+]?\d+(?:\.\d+)?$/', $str)) {
            $val = (float) $str;
            if ($val < 0) throw new InvalidArgumentException('Negative duration not allowed');
            return (int) floor($val);
        }

        $units = [
            // seconds
            's' => 1, 'sec' => 1, 'secs' => 1, 'second' => 1, 'seconds' => 1,
            // minutes
            'm' => 60, 'min' => 60, 'mins' => 60, 'minute' => 60, 'minutes' => 60,
            // hours
            'h' => 3600, 'hr' => 3600, 'hrs' => 3600, 'hour' => 3600, 'hours' => 3600,
            // days
            'd' => 86400, 'day' => 86400, 'days' => 86400,
            // weeks
            'w' => 604800, 'week' => 604800, 'weeks' => 604800,
            // months (fixed 30 days)
            'mo' => 2592000, 'mon' => 2592000, 'month' => 2592000, 'months' => 2592000,
            // years (fixed 365 days)
            'y' => 31536000, 'yr' => 31536000, 'year' => 31536000, 'years' => 31536000,
            // decades (10 years)
            'dec' => 315360000,
            'decade' => 315360000, 'decades' => 315360000,
            // centuries (100 years)
            'cen' => 3153600000,
            'century' => 3153600000, 'centuries' => 3153600000,
            // millennia (1000 years)
            'mil' => 31536000000,
            'millennium' => 31536000000, 'millennia' => 31536000000,
        ];

        $total = 0.0;
        $matched = false;
        $pattern = '/(?:(?<num>[-+]?\d+(?:\.\d+)?)\s*(?<unit>[a-zA-Z]+))|(?<bare>[-+]?\d+(?:\.\d+)?)/';
        if (preg_match_all($pattern, $str, $m, PREG_SET_ORDER)) {
            foreach ($m as $tok) {
                if (!empty($tok['bare']) && empty($tok['unit'])) {
                    // Bare number without unit: interpret as seconds (but only if entire tokenization is just this)
                    $val = (float) $tok['bare'];
                    if ($val < 0) throw new InvalidArgumentException('Negative duration not allowed');
                    $total += $val;
                    $matched = true;
                    continue;
                }
                $num = isset($tok['num']) ? (float) $tok['num'] : null;
                $unit = isset($tok['unit']) ? $tok['unit'] : null;
                if ($num === null || $unit === null) continue;
                if ($num < 0) throw new InvalidArgumentException('Negative duration not allowed');
                if (!array_key_exists($unit, $units)) {
                    throw new InvalidArgumentException('Unknown unit: ' . $unit);
                }
                $total += $num * $units[$unit];
                $matched = true;
            }
        }

        if (!$matched) {
            throw new InvalidArgumentException('Could not parse duration');
        }

        return (int) floor(max(0, $total));
    }

    public static function humanizeSeconds(int $seconds): string
    {
        $seconds = max(0, (int) $seconds);
        $parts = [];
        $units = [
            'mil' => 31536000000, // 1000y
            'cen' => 3153600000,  // 100y
            'dec' => 315360000,   // 10y
            'y' => 31536000,      // 365d
            'mo' => 2592000,      // 30d
            'w' => 604800,        // 7d
            'd' => 86400,
        ];

        foreach ($units as $label => $size) {
            if ($seconds >= $size) {
                $v = intdiv($seconds, $size);
                $seconds -= $v * $size;
                $parts[] = $v.$label;
            }
        }

        $h = intdiv($seconds, 3600); $seconds %= 3600;
        $m = intdiv($seconds, 60);   $s = $seconds % 60;
        $hms = sprintf('%02d:%02d:%02d', $h, $m, $s);

        if (empty($parts)) {
            return $hms;
        }
        return implode(' ', $parts).' '.$hms;
    }

    /**
     * Compact colon format Y:W:DD:HH:MM:SS
     */
    public static function compactColon(int $seconds): string
    {
        $s = max(0, (int) $seconds);
        $Y   = 31536000;    // 1 year (365d)
        $W   = 604800;      // 7 days
        $D   = 86400;       // 1 day

        $y   = intdiv($s, $Y);   $s %= $Y;
        $w   = intdiv($s, $W);   $s %= $W;
        $dd  = intdiv($s, $D);   $s %= $D;
        $hh  = intdiv($s, 3600); $s %= 3600;
        $mm  = intdiv($s, 60);   $ss = $s % 60;

        return implode(':', [
            str_pad((string)$y, 3, '0', STR_PAD_LEFT),
            str_pad((string)$w, 2, '0', STR_PAD_LEFT),
            str_pad((string)$dd, 2, '0', STR_PAD_LEFT),
            str_pad((string)$hh, 2, '0', STR_PAD_LEFT),
            str_pad((string)$mm, 2, '0', STR_PAD_LEFT),
            str_pad((string)$ss, 2, '0', STR_PAD_LEFT),
        ]);
    }
}
