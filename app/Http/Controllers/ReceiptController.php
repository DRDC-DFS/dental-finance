<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceiptController extends Controller
{
    public function kwitansi($incomeTransaction)
    {
        // =========
        // Setting Klinik (sementara hardcode, nanti bisa dibuat setting table)
        // =========
        $clinicName = 'DR DENTAL CARE';
        $ownerName  = 'drg. Desly A.C. Luhulima, M.K.M';

        // =========
        // Logo & TTD (taruh file di: public/images/)
        // - logo.png  => public/images/logo.png
        // - ttd.png   => public/images/ttd.png
        // =========
        $logoBase64 = $this->toBase64(public_path('images/logo.png'));
        $ttdBase64  = $this->toBase64(public_path('images/ttd.png'));

        // =========
        // Header transaksi
        // NOTE: sesuai error kamu sebelumnya, kita pakai invoice_number (bukan it.code)
        // =========
        $trx = DB::table('income_transactions as it')
            ->leftJoin('patients as p', 'p.id', '=', 'it.patient_id')
            ->leftJoin('doctors as d', 'd.id', '=', 'it.doctor_id')
            ->where('it.id', $incomeTransaction)
            ->selectRaw("
                it.id,
                it.trx_date,
                it.status,
                it.pay_total,
                p.name as patient_name,
                d.name as doctor_name,
                d.type as doctor_type,
                it.invoice_number as invoice_code
            ")
            ->first();

        abort_if(!$trx, 404, 'Transaksi tidak ditemukan.');

        // =========
        // Item tindakan
        // NOTE: dari log query kamu, FK item adalah iti.transaction_id (bukan income_transaction_id)
        // NOTE: kolom item_name yang dipakai view => alias dari t.name
        // NOTE: kolom price tidak ada di table item (error kamu), jadi ambil dari t.price
        // =========
        $items = DB::table('income_transaction_items as iti')
            ->leftJoin('treatments as t', 't.id', '=', 'iti.treatment_id')
            ->where('iti.transaction_id', $incomeTransaction)
            ->orderBy('iti.id', 'asc')
            ->selectRaw("
                t.name  as item_name,
                iti.qty as qty,
                t.price as price,
                iti.subtotal as subtotal
            ")
            ->get();

        return view('receipts.kwitansi', [
            'trx'        => $trx,
            'items'      => $items,
            'clinicName' => $clinicName,
            'ownerName'  => $ownerName,
            'logoBase64' => $logoBase64,
            'ttdBase64'  => $ttdBase64,
        ]);
    }

    private function toBase64(string $path): ?string
    {
        if (!file_exists($path)) return null;

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png'  => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            default => null
        };

        if (!$mime) return null;

        $data = base64_encode(file_get_contents($path));
        return "data:{$mime};base64,{$data}";
    }
}