<?php

use Carbon\Carbon;

if (!function_exists('tanggal_id')) {
    /**
     * Format tanggal Indonesia yang konsisten.
     * $style:
     *   - 'long'  => 05 Maret 2026
     *   - 'short' => 05/03/2026
     */
    function tanggal_id($date, string $style = 'long'): string
    {
        if (empty($date)) return '-';

        try {
            $c = $date instanceof Carbon ? $date : Carbon::parse($date);

            return $style === 'short'
                ? $c->format('d/m/Y')
                : $c->translatedFormat('d F Y');
        } catch (\Throwable $e) {
            return '-';
        }
    }
}

if (!function_exists('tanggal_jam_id')) {
    /**
     * Format tanggal + jam Indonesia.
     * $style:
     *   - 'long'  => 05 Maret 2026 14:30
     *   - 'short' => 05/03/2026 14:30
     */
    function tanggal_jam_id($dateTime, string $style = 'long'): string
    {
        if (empty($dateTime)) return '-';

        try {
            $c = $dateTime instanceof Carbon ? $dateTime : Carbon::parse($dateTime);

            return $style === 'short'
                ? $c->format('d/m/Y H:i')
                : $c->translatedFormat('d F Y H:i');
        } catch (\Throwable $e) {
            return '-';
        }
    }
}

if (!function_exists('bulan_id')) {
    /**
     * Format bulan Indonesia.
     * Example: Maret 2026
     */
    function bulan_id($date): string
    {
        if (empty($date)) return '-';

        try {
            $c = $date instanceof Carbon ? $date : Carbon::parse($date);
            return $c->translatedFormat('F Y');
        } catch (\Throwable $e) {
            return '-';
        }
    }
}