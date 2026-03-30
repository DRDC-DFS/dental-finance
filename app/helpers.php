<?php

use Carbon\Carbon;

/**
 * Format Rupiah Indonesia.
 * Default: "Rp 240.000"
 */
if (!function_exists('rupiah')) {
    function rupiah($value, string $prefix = 'Rp '): string
    {
        $num = (float) ($value ?? 0);
        return $prefix . number_format($num, 0, ',', '.');
    }
}

/**
 * Format angka Indonesia (tanpa prefix).
 * Contoh:
 * angka_id(350000) => "350.000"
 * angka_id(1.5, 2) => "1,50"
 */
if (!function_exists('angka_id')) {
    function angka_id($value, int $decimals = 0): string
    {
        $num = (float) ($value ?? 0);
        return number_format($num, $decimals, ',', '.');
    }
}

/**
 * Format tanggal Indonesia dengan Carbon (lokal id).
 * Contoh:
 * tgl_id('2026-03-05') => "05 Maret 2026"
 * tgl_id('2026-03-05', 'd/m/Y') => "05/03/2026"
 * tgl_id('2026-03-05', 'd M Y') => "05 Mar 2026"
 */
if (!function_exists('tgl_id')) {
    function tgl_id($date, string $format = 'd F Y'): string
    {
        if (empty($date)) return '-';

        try {
            return Carbon::parse($date)
                ->locale('id')
                ->translatedFormat($format);
        } catch (\Throwable $e) {
            return (string) $date;
        }
    }
}

/**
 * Alias helper lama: format_rupiah()
 * Mengarah ke rupiah() supaya kompatibel.
 */
if (!function_exists('format_rupiah')) {
    function format_rupiah($value, string $prefix = 'Rp '): string
    {
        return rupiah($value, $prefix);
    }
}

/**
 * Bersihkan input rupiah string jadi angka.
 * Contoh: "Rp 350.000" -> "350000"
 */
if (!function_exists('clean_rupiah')) {
    function clean_rupiah(string $value): string
    {
        $value = trim($value);
        $value = str_replace(['Rp', 'rp', ' ', '.'], '', $value);
        $value = str_replace(',', '.', $value);
        $value = preg_replace('/[^0-9.]/', '', $value) ?? '';
        return $value === '' ? '0' : $value;
    }
}