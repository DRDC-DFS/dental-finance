@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-6 py-6">

    <style>
        .card{
            background:#fff;border:1px solid #e5e7eb;border-radius:14px;
            box-shadow:0 10px 25px rgba(0,0,0,.08);
        }
        .card-h{padding:16px 18px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;gap:12px}
        .card-b{padding:16px 18px}
        .label{display:block;font-size:13px;font-weight:800;color:#374151;margin-bottom:6px}
        .input,.select,.textarea{
            width:100%;border:1px solid #d1d5db;border-radius:10px;
            padding:10px 12px;font-size:14px;outline:none;
        }
        .input:focus,.select:focus,.textarea:focus{border-color:#2563eb;box-shadow:0 0 0 3px rgba(37,99,235,.15)}
        .btn{
            display:inline-block;border-radius:10px;padding:10px 16px;
            font-weight:900;text-decoration:none;border:0;cursor:pointer;
        }
        .btn-primary{background:#2563eb;color:#fff;box-shadow:0 6px 14px rgba(37,99,235,.25)}
        .btn-primary:hover{background:#1d4ed8}
        .btn-danger{background:#dc2626;color:#fff;box-shadow:0 6px 14px rgba(220,38,38,.25)}
        .btn-danger:hover{background:#b91c1c}
        .btn-secondary{background:#e5e7eb;color:#111827}
        .btn-secondary:hover{background:#d1d5db}
        .btn-outline{
            background:#fff;color:#111827;border:1px solid #d1d5db;
        }
        .btn-outline:hover{background:#f9fafb}
        .btn-warning{
            background:#fff7ed;color:#9a3412;border:1px solid #fdba74;
        }
        .btn-warning:hover{
            background:#ffedd5;
        }
        .btn[disabled]{
            opacity:.6;
            cursor:not-allowed;
            box-shadow:none;
        }
        .badge{
            display:inline-block;border-radius:999px;padding:4px 10px;
            font-weight:900;font-size:12px;border:1px solid transparent;
        }
        .grid2{display:grid;grid-template-columns:1fr;gap:14px}
        @media(min-width:768px){.grid2{grid-template-columns:1fr 1fr}}
        .grid4{display:grid;grid-template-columns:1fr;gap:14px}
        @media(min-width:768px){.grid4{grid-template-columns:2fr 1fr 1fr}}
        .grid5{display:grid;grid-template-columns:1fr;gap:14px}
        @media(min-width:768px){.grid5{grid-template-columns:2fr 1fr 1fr 1fr}}
        .table{width:100%;border-collapse:collapse;font-size:14px}
        .table th{background:#f8fafc;border-bottom:1px solid #e5e7eb;text-align:left;padding:14px 16px}
        .table td{border-bottom:1px solid #e5e7eb;padding:14px 16px;vertical-align:top}
        .right{text-align:right}
        .muted{color:#6b7280}
        .topbar{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:16px}
        .actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap}
        .mb20{margin-bottom:20px}
        .preview-box{
            background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;
            padding:10px 12px;
        }
        .preview-row{display:flex;justify-content:space-between;gap:10px;align-items:center}
        .preview-row b{color:#111827}
        .manual-box{
            background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;
            padding:12px 14px;color:#1e3a8a;
        }
        .fixed-box{
            background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;
            padding:12px 14px;color:#166534;
        }
        .summary-grid{
            display:grid;
            grid-template-columns:1fr;
            gap:12px;
        }
        @media(min-width:768px){
            .summary-grid{grid-template-columns:repeat(4,1fr);}
        }
        .summary-box{
            border:1px solid #e5e7eb;
            border-radius:12px;
            padding:12px 14px;
            background:#fafafa;
        }
        .summary-box .title{
            font-size:12px;
            color:#6b7280;
            font-weight:700;
            margin-bottom:4px;
        }
        .summary-box .value{
            font-size:24px;
            font-weight:900;
            color:#111827;
        }
        .summary-box.success{
            background:#f0fdf4;
            border-color:#bbf7d0;
        }
        .summary-box.success .value{
            color:#166534;
        }
        .summary-box.warning{
            background:#fff7ed;
            border-color:#fdba74;
        }
        .summary-box.warning .value{
            color:#9a3412;
        }
        .payment-row{
            border:1px solid #dbeafe;
            background:#f8fbff;
            border-radius:12px;
            padding:14px;
            margin-bottom:12px;
        }
        .payment-row-grid{
            display:grid;
            grid-template-columns:1fr;
            gap:12px;
        }
        @media(min-width:768px){
            .payment-row-grid{
                grid-template-columns:1.4fr 1fr 1fr auto;
                align-items:end;
            }
        }
        .ortho-box{
            background:#fff7ed;
            border:1px solid #fdba74;
            border-radius:12px;
            padding:12px 14px;
            color:#9a3412;
        }
        .prosto-box{
            background:#eff6ff;
            border:1px solid #bfdbfe;
            border-radius:12px;
            padding:12px 14px;
            color:#1d4ed8;
        }
        .inline-case-box{
            background:#f8fafc;
            border:1px solid #cbd5e1;
            border-radius:12px;
            padding:12px 14px;
        }
        .discount-box{
            background:#fffbeb;
            border:1px solid #fde68a;
            border-radius:12px;
            padding:12px 14px;
            color:#92400e;
        }
        .focus-ring-target{
            scroll-margin-top: 24px;
        }
        .item-edit-grid{
            display:grid;
            grid-template-columns:1fr;
            gap:8px;
        }
        .item-edit-actions{
            display:flex;
            gap:10px;
            align-items:center;
            flex-wrap:wrap;
        }
        .payment-status-box{
            border-radius:12px;
            padding:12px 14px;
            margin-bottom:16px;
            border:1px solid #e5e7eb;
            display:none;
        }
        .payment-status-box.info{
            background:#eff6ff;
            border-color:#bfdbfe;
            color:#1d4ed8;
        }
        .payment-status-box.success{
            background:#f0fdf4;
            border-color:#bbf7d0;
            color:#166534;
        }
        .payment-status-box.danger{
            background:#fef2f2;
            border-color:#fecaca;
            color:#991b1b;
        }
        .free-pill{
            display:inline-block;
            margin-left:8px;
            padding:2px 8px;
            border-radius:999px;
            font-size:11px;
            font-weight:900;
            background:#dcfce7;
            color:#166534;
            border:1px solid #86efac;
            vertical-align:middle;
        }
        .free-treatment-note{
            margin-top:8px;
            padding:10px 12px;
            border-radius:10px;
            background:#f0fdf4;
            border:1px solid #bbf7d0;
            color:#166534;
            font-size:13px;
            display:none;
        }
    </style>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-green-100 border border-green-300 p-4 text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-6 rounded-lg bg-red-100 border border-red-300 p-4 text-red-800">
            <div class="font-semibold mb-1">Terjadi error:</div>
            <ul class="list-disc ml-5">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $st = strtolower($incomeTransaction->status ?? 'draft');
        $badgeStyle = 'background:#e5e7eb;color:#111827;border-color:#e5e7eb;';
        if ($st === 'paid') $badgeStyle = 'background:#dcfce7;color:#166534;border-color:#86efac;';
        if ($st === 'cancelled' || $st === 'void') $badgeStyle = 'background:#fee2e2;color:#991b1b;border-color:#fecaca;';

        $trxDateValue = $incomeTransaction->trx_date
            ? \Carbon\Carbon::parse($incomeTransaction->trx_date)->format('Y-m-d')
            : now()->format('Y-m-d');

        $billTotalBase = (float) $incomeTransaction->bill_total;
        $payerType = old('payer_type', $incomeTransaction->payer_type ?? 'umum');
        $orthoCaseMode = old('ortho_case_mode', $incomeTransaction->ortho_case_mode ?? 'none');
        $prostoCaseMode = old('prosto_case_mode', $incomeTransaction->prosto_case_mode ?? 'none');
        $isBpjs = strtolower((string) $payerType) === 'bpjs';
        $isKhusus = strtolower((string) $payerType) === 'khusus';
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $isOwner = $role === 'owner';
        $isPaidTransaction = strtolower((string) ($incomeTransaction->status ?? 'draft')) === 'paid';
        $isKhususZeroBill = $isKhusus && $billTotalBase <= 0;
        $showPaymentMethodSection = !$isBpjs && !$isPaidTransaction && (!$isKhusus || !$isKhususZeroBill);
        $showZeroBillKhususConfirmation = !$isBpjs && !$isPaidTransaction && $isKhusus && $isKhususZeroBill;

        $paidTotal = (float) ($incomeTransaction->pay_total ?? 0);
        $remainingTotal = max(0, $billTotalBase - $paidTotal);

        $paymentCount = isset($payments) ? count($payments) : 0;

        $oldMethodIds = old('payment_method_id', []);
        $oldChannels = old('channel', []);
        $oldAmounts = old('amount', []);

        $paymentRowCount = max(
            2,
            count(is_array($oldMethodIds) ? $oldMethodIds : []),
            count(is_array($oldChannels) ? $oldChannels : []),
            count(is_array($oldAmounts) ? $oldAmounts : [])
        );
    @endphp

    <div class="topbar">
        <div>
            <h1 style="font-size:40px;font-weight:900;margin:0;">Edit Transaksi</h1>
            <div class="muted" style="margin-top:6px;">
                Invoice: <span style="font-weight:900;color:#111827;">{{ $incomeTransaction->invoice_number }}</span>
                <span style="margin:0 10px;">•</span>
                Status:
                <span class="badge" style="{{ $badgeStyle }}">{{ strtoupper($incomeTransaction->status) }}</span>
                <span style="margin:0 10px;">•</span>
                Kategori:
                <span class="badge" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">
                    {{ strtoupper($payerType) }}
                </span>
                <span style="margin:0 10px;">•</span>
                Mode Ortho:
                <span class="badge" style="background:#fff7ed;color:#9a3412;border-color:#fdba74;">
                    {{ strtoupper($orthoCaseMode) }}
                </span>
                <span style="margin:0 10px;">•</span>
                Mode Prosto:
                <span class="badge" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;">
                    {{ strtoupper($prostoCaseMode) }}
                </span>
            </div>
        </div>

        <div class="actions">
            <a href="{{ route('income.index') }}" class="btn btn-secondary">← Kembali</a>

            @if($isOwner)
                <a href="{{ route('owner_finance.create', ['income_id' => $incomeTransaction->id]) }}" class="btn btn-secondary">
                    Owner Finance
                </a>
            @endif

            <form method="POST"
                  action="{{ route('income.destroy', ['income' => $incomeTransaction->id]) }}"
                  onsubmit="return confirm('Hapus transaksi ini? Semua item tindakan akan ikut terhapus.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Hapus Transaksi</button>
            </form>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-b">
            <div class="summary-grid">
                <div class="summary-box">
                    <div class="title">Total Tagihan</div>
                    <div class="value">{{ format_rupiah($billTotalBase) }}</div>
                </div>
                <div class="summary-box">
                    <div class="title">Total Sudah Dibayar</div>
                    <div class="value">{{ format_rupiah($paidTotal) }}</div>
                </div>
                <div class="summary-box warning">
                    <div class="title">Sisa Tagihan</div>
                    <div class="value">{{ format_rupiah($remainingTotal) }}</div>
                </div>
                <div class="summary-box success">
                    <div class="title">Jumlah Metode Pembayaran Tersimpan</div>
                    <div class="value">{{ $paymentCount }}</div>
                </div>
            </div>

            <div class="muted" style="font-size:12px;margin-top:10px;">
                * Pasien bisa membayar dengan <b>lebih dari satu metode</b> dalam satu kali input. Contoh: <b>Tunai + BCA Transfer</b>, atau <b>BCA Transfer + BNI Transfer</b>.
            </div>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-h">
            <h2 style="font-size:18px;font-weight:900;margin:0;">Data Transaksi</h2>
        </div>

        <div class="card-b">
            <form method="POST" action="{{ route('income.update', ['income' => $incomeTransaction->id]) }}" class="grid2">
                @csrf
                @method('PUT')

                <div>
                    <label class="label">Tanggal</label>
                    <input type="date" name="trx_date" class="input"
                           value="{{ old('trx_date', $trxDateValue) }}">
                </div>

                <div>
                    <label class="label">Dokter</label>
                    <select name="doctor_id" class="select">
                        @foreach($doctors as $doc)
                            <option value="{{ $doc->id }}"
                                @selected(old('doctor_id', $incomeTransaction->doctor_id) == $doc->id)>
                                {{ $doc->name }} ({{ strtoupper($doc->type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="label">Nama Pasien</label>
                    <input type="text" name="patient_name" class="input"
                           value="{{ old('patient_name', $incomeTransaction->patient?->name) }}"
                           placeholder="Nama pasien">
                </div>

                <div>
                    <label class="label">No HP (opsional)</label>
                    <input type="text" name="patient_phone" class="input"
                           value="{{ old('patient_phone', $incomeTransaction->patient?->phone) }}"
                           placeholder="08xxxxxxxxxx">
                </div>

                <div>
                    <label class="label">Kategori Pasien</label>
                    <select name="payer_type" class="select">
                        <option value="umum" @selected($payerType === 'umum')>UMUM</option>
                        <option value="bpjs" @selected($payerType === 'bpjs')>BPJS</option>
                        <option value="khusus" @selected($payerType === 'khusus')>KHUSUS</option>
                    </select>
                </div>

                <div>
                    <label class="label">Visibility</label>
                    <select name="visibility" class="select">
                        <option value="public"  @selected(old('visibility', $incomeTransaction->visibility) == 'public')>PUBLIC</option>
                        <option value="private" @selected(old('visibility', $incomeTransaction->visibility) == 'private')>PRIVATE (Owner only)</option>
                    </select>
                </div>

                <div>
                    <label class="label">Catatan (opsional)</label>
                    <input type="text" name="notes" class="input"
                           value="{{ old('notes', $incomeTransaction->notes) }}"
                           placeholder="Catatan transaksi">
                </div>

                <input type="hidden" name="ortho_case_mode" value="{{ $orthoCaseMode }}">
                <input type="hidden" name="prosto_case_mode" value="{{ $prostoCaseMode }}">

                <div style="grid-column:1/-1;display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan Header</button>
                </div>
            </form>
        </div>
    </div>

    <div id="add-item-form" class="focus-ring-target"></div>

    <div class="card mb20">
        <div class="card-h">
            <h2 style="font-size:18px;font-weight:900;margin:0;">Tambah Tindakan</h2>
            <div class="muted">
                Total Tagihan: <span style="font-weight:900;color:#111827;" id="bill_total_label">{{ format_rupiah($incomeTransaction->bill_total) }}</span>
            </div>
        </div>

        <div class="card-b">
            @if($isBpjs)
                <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:12px;padding:12px 14px;margin-bottom:14px;">
                    <b>Mode BPJS aktif.</b> Tindakan tetap bisa ditambahkan, tetapi harga, tagihan, fee dokter, dan diskon akan otomatis bernilai <b>Rp0</b>.
                </div>
            @endif

            @if($isKhusus)
                <div class="discount-box" style="margin-bottom:14px;">
                    <b>Mode KHUSUS aktif.</b> Harga master tetap dipakai, tetapi admin bisa isi <b>Diskon</b> sampai penuh. Cocok untuk pasien keluarga, free treatment, atau diskon khusus tanpa mengubah harga master.
                </div>
            @endif

            <form method="POST" action="{{ route('income.items.store', $incomeTransaction->id) }}" class="grid5" id="addItemForm">
                @csrf

                <div style="grid-column:1/-1;">
                    <label class="label">Tindakan</label>
                    <select id="treatment_id" name="treatment_id" class="select">
                        <option value="">- Pilih tindakan -</option>
                        @foreach($treatments as $t)
                            <option value="{{ $t->id }}"
                                    data-price="{{ (float) $t->price }}"
                                    data-price-mode="{{ strtolower((string) ($t->price_mode ?? 'fixed')) }}"
                                    data-notes-hint="{{ e((string) ($t->notes_hint ?? '')) }}"
                                    data-unit="{{ e((string) ($t->unit ?? '1x')) }}"
                                    data-is-free="{{ (int) ($t->is_free ?? 0) }}"
                                    data-is-ortho="{{ (int) ($t->is_ortho_related ?? 0) }}"
                                    data-is-prosto="{{ (int) ($t->is_prosto_related ?? 0) }}">
                                {{ $t->name }} ({{ $t->unit }}) - {{ format_rupiah($t->price) }}
                                [{{ strtolower((string) ($t->price_mode ?? 'fixed')) === 'manual' ? 'MANUAL' : 'FIXED' }}]{{ (int) ($t->is_free ?? 0) === 1 ? ' [FREE]' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div id="ortho_auto_notice_wrap" style="grid-column:1/-1;display:none;">
                    <div class="ortho-box">
                        <b>Tindakan ortho terdeteksi.</b>
                        Silakan pilih <b>Mode Kasus Ortho</b> sebelum melanjutkan.
                    </div>
                </div>

                <div id="ortho_case_mode_inline_wrap" style="grid-column:1/-1;display:none;">
                    <div class="inline-case-box">
                        <div class="grid2">
                            <div>
                                <label class="label">Mode Kasus Ortho</label>
                                <select name="ortho_case_mode" id="ortho_case_mode_select_inline" class="select" form="orthoCaseModeForm">
                                    <option value="none" @selected($orthoCaseMode === 'none')>BUKAN KASUS ORTHO</option>
                                    <option value="biasa" @selected($orthoCaseMode === 'biasa')>ORTHO BIASA</option>
                                    <option value="lanjutan" @selected($orthoCaseMode === 'lanjutan')>ORTHO LANJUTAN</option>
                                </select>
                            </div>

                            <div style="display:flex;align-items:end;justify-content:flex-end;">
                                <button type="submit" form="orthoCaseModeForm" class="btn btn-primary">Simpan Mode Ortho</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="prosto_auto_notice_wrap" style="grid-column:1/-1;display:none;">
                    <div class="prosto-box">
                        <b>Tindakan prosto terdeteksi.</b>
                        Silakan pilih <b>Mode Kasus Prosto</b> sebelum melanjutkan.
                    </div>
                </div>

                <div id="prosto_case_mode_inline_wrap" style="grid-column:1/-1;display:none;">
                    <div class="inline-case-box">
                        <div class="grid2">
                            <div>
                                <label class="label">Mode Kasus Prosto</label>
                                <select name="prosto_case_mode" id="prosto_case_mode_select_inline" class="select" form="prostoCaseModeForm">
                                    <option value="none" @selected($prostoCaseMode === 'none')>BUKAN KASUS PROSTO</option>
                                    <option value="biasa" @selected($prostoCaseMode === 'biasa')>PROSTO BIASA</option>
                                    <option value="lanjutan" @selected($prostoCaseMode === 'lanjutan')>PROSTO LANJUTAN</option>
                                </select>
                            </div>

                            <div style="display:flex;align-items:end;justify-content:flex-end;">
                                <button type="submit" form="prostoCaseModeForm" class="btn btn-primary">Simpan Mode Prosto</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="label">Qty</label>
                    <input id="qty" type="number" step="0.01" min="0.01" name="qty" class="input"
                           value="{{ old('qty', 1) }}">
                </div>

                <div>
                    <label class="label">Harga (Rp)</label>
                    <input id="unit_price" type="text" name="unit_price" class="input rupiah-input"
                           value="{{ $isBpjs ? '0' : old('unit_price') }}"
                           placeholder="contoh: 150.000"
                           {{ $isBpjs ? 'readonly' : '' }}>
                    <div class="muted" style="font-size:12px;margin-top:6px;" id="unit_price_help">
                        @if($isBpjs)
                            Karena BPJS, harga otomatis 0.
                        @else
                            Pilih tindakan terlebih dahulu.
                        @endif
                    </div>
                    <div id="free_treatment_notice" class="free-treatment-note">
                        ✔ Treatment Gratis aktif — harga otomatis 0 dan pasien tetap kategori UMUM.
                    </div>
                </div>

                <div>
                    <label class="label">Diskon (Rp)</label>
                    <input id="discount_amount" type="text" name="discount_amount" class="input rupiah-input"
                           value="{{ $isBpjs ? '0' : old('discount_amount', 0) }}"
                           placeholder="contoh: 50.000"
                           {{ $isBpjs ? 'readonly' : '' }}>
                    <div class="muted" style="font-size:12px;margin-top:6px;" id="discount_help">
                        @if($isBpjs)
                            Karena BPJS, diskon otomatis 0.
                        @elseif($isKhusus)
                            Isi diskon nominal. Bisa sampai penuh untuk Family/Gratis.
                        @else
                            Biarkan 0 bila tidak ada diskon.
                        @endif
                    </div>
                </div>

                <div style="display:flex;align-items:end;">
                    <button type="button" id="freeTreatmentBtn" class="btn btn-warning" style="width:100%;{{ $isBpjs ? 'display:none;' : '' }}">
                        Gratis / Family
                    </button>
                </div>

                <div style="grid-column:1/-1;">
                    <div id="treatment_mode_info" class="fixed-box" style="display:none;">
                        <b id="treatment_mode_title">Mode Harga Tetap</b>
                        <div id="treatment_mode_desc" style="margin-top:6px;font-size:13px;">
                            Harga otomatis mengikuti Master Tindakan.
                        </div>
                    </div>
                </div>

                <div style="grid-column:1/-1;">
                    <div id="notes_hint_box" class="manual-box" style="display:none;">
                        <b>Petunjuk Input</b>
                        <div id="notes_hint_text" style="margin-top:6px;font-size:13px;">-</div>
                    </div>
                </div>

                <div style="grid-column:1/-1;">
                    <div class="preview-box">
                        <div class="preview-row">
                            <span class="muted">Preview Harga Kotor (qty × harga)</span>
                            <b id="preview_gross_subtotal">Rp 0</b>
                        </div>
                        <div class="preview-row" style="margin-top:6px;">
                            <span class="muted">Preview Diskon</span>
                            <b id="preview_discount">Rp 0</b>
                        </div>
                        <div class="preview-row" style="margin-top:6px;">
                            <span class="muted">Preview Subtotal Akhir</span>
                            <b id="preview_subtotal">Rp 0</b>
                        </div>
                        <div class="preview-row" style="margin-top:6px;">
                            <span class="muted">Preview Total Tagihan Baru</span>
                            <b id="preview_total_new">{{ format_rupiah($incomeTransaction->bill_total) }}</b>
                        </div>
                        <div class="muted" style="font-size:12px;margin-top:6px;">
                            * Harga master tetap tersimpan. Diskon hanya mengurangi tagihan akhir.
                        </div>
                    </div>
                </div>

                <div style="grid-column:1/-1;display:flex;justify-content:flex-end;">
                    <button type="submit" class="btn btn-primary">+ Tambah Item</button>
                </div>
            </form>

            <form method="POST" action="{{ route('income.update', ['income' => $incomeTransaction->id]) }}" id="orthoCaseModeForm" style="display:none;">
                @csrf
                @method('PUT')
                <input type="hidden" name="trx_date" value="{{ old('trx_date', $trxDateValue) }}">
                <input type="hidden" name="doctor_id" value="{{ old('doctor_id', $incomeTransaction->doctor_id) }}">
                <input type="hidden" name="patient_name" value="{{ old('patient_name', $incomeTransaction->patient?->name) }}">
                <input type="hidden" name="patient_phone" value="{{ old('patient_phone', $incomeTransaction->patient?->phone) }}">
                <input type="hidden" name="payer_type" value="{{ $payerType }}">
                <input type="hidden" name="visibility" value="{{ old('visibility', $incomeTransaction->visibility) }}">
                <input type="hidden" name="notes" value="{{ old('notes', $incomeTransaction->notes) }}">
                <input type="hidden" name="prosto_case_mode" value="{{ $prostoCaseMode }}">
            </form>

            <form method="POST" action="{{ route('income.update', ['income' => $incomeTransaction->id]) }}" id="prostoCaseModeForm" style="display:none;">
                @csrf
                @method('PUT')
                <input type="hidden" name="trx_date" value="{{ old('trx_date', $trxDateValue) }}">
                <input type="hidden" name="doctor_id" value="{{ old('doctor_id', $incomeTransaction->doctor_id) }}">
                <input type="hidden" name="patient_name" value="{{ old('patient_name', $incomeTransaction->patient?->name) }}">
                <input type="hidden" name="patient_phone" value="{{ old('patient_phone', $incomeTransaction->patient?->phone) }}">
                <input type="hidden" name="payer_type" value="{{ $payerType }}">
                <input type="hidden" name="visibility" value="{{ old('visibility', $incomeTransaction->visibility) }}">
                <input type="hidden" name="notes" value="{{ old('notes', $incomeTransaction->notes) }}">
                <input type="hidden" name="ortho_case_mode" value="{{ $orthoCaseMode }}">
            </form>
        </div>
    </div>

    <div class="card mb20">
        <div class="card-h">
            <h2 style="font-size:18px;font-weight:900;margin:0;">Item Tindakan</h2>
            <div class="muted">
                Total: <span style="font-weight:900;color:#111827;">{{ format_rupiah($incomeTransaction->bill_total) }}</span>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tindakan</th>
                        <th class="right">Qty</th>
                        <th class="right">Harga</th>
                        <th class="right">Diskon</th>
                        <th class="right">Subtotal</th>
                        <th style="width:260px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($incomeTransaction->items as $item)
                        @php
                            $itemTreatment = $item->treatment;
                            $itemPriceMode = strtolower((string) ($itemTreatment->price_mode ?? 'fixed'));
                            $itemNotesHint = trim((string) ($itemTreatment->notes_hint ?? ''));
                            $formId = 'update-item-form-' . $item->id;
                            $itemDiscount = (float) ($item->discount_amount ?? 0);
                            $itemIsOrtho = (bool) ($itemTreatment->is_ortho_related ?? false);
                            $itemIsProsto = (bool) ($itemTreatment->is_prosto_related ?? false);
                        @endphp
                        <tr>
                            <td style="font-weight:900;">
                                {{ $itemTreatment?->name ?? '-' }}
                                @if((int) ($itemTreatment->is_free ?? 0) === 1)
                                    <span class="free-pill">FREE</span>
                                @endif
                                @if($itemIsOrtho)
                                    <span class="free-pill" style="background:#ffedd5;color:#9a3412;border-color:#fdba74;">ORTHO</span>
                                @endif
                                @if($itemIsProsto)
                                    <span class="free-pill" style="background:#dbeafe;color:#1d4ed8;border-color:#93c5fd;">PROSTO</span>
                                @endif
                                <div class="muted" style="font-size:12px;margin-top:4px;">
                                    Mode Harga:
                                    <b>{{ $itemPriceMode === 'manual' ? 'MANUAL' : 'FIXED' }}</b>
                                </div>
                                @if($itemNotesHint !== '')
                                    <div class="muted" style="font-size:12px;margin-top:4px;">
                                        {{ $itemNotesHint }}
                                    </div>
                                @endif
                            </td>

                            <td class="right">
                                <input type="number"
                                       step="0.01"
                                       min="0.01"
                                       name="qty"
                                       value="{{ $item->qty }}"
                                       form="{{ $formId }}"
                                       class="input"
                                       style="width:90px;text-align:right;padding:8px 10px;">
                            </td>

                            <td class="right">
                                <input type="text"
                                       name="unit_price"
                                       value="{{ number_format((float) $item->unit_price, 0, ',', '.') }}"
                                       form="{{ $formId }}"
                                       class="input rupiah-input"
                                       style="width:140px;text-align:right;padding:8px 10px;"
                                       placeholder="150.000"
                                       {{ $isBpjs ? 'readonly' : '' }}
                                       @if(!$isBpjs && $itemPriceMode === 'fixed') readonly @endif>
                                <div class="muted" style="font-size:12px;margin-top:6px;">
                                    @if($isBpjs)
                                        Karena BPJS, harga otomatis 0.
                                    @elseif((int) ($itemTreatment->is_free ?? 0) === 1)
                                        Treatment gratis: harga otomatis 0.
                                    @elseif($itemPriceMode === 'fixed')
                                        Harga mengikuti Master Tindakan.
                                    @else
                                        Harga manual sesuai kasus pasien.
                                    @endif
                                </div>
                            </td>

                            <td class="right">
                                <div class="item-edit-grid">
                                    <input type="text"
                                           name="discount_amount"
                                           value="{{ number_format($itemDiscount, 0, ',', '.') }}"
                                           form="{{ $formId }}"
                                           class="input rupiah-input item-discount-input"
                                           style="width:130px;text-align:right;padding:8px 10px;"
                                           placeholder="0"
                                           {{ $isBpjs ? 'readonly' : '' }}>
                                    <button type="button"
                                            class="btn btn-warning item-free-btn"
                                            data-form="{{ $formId }}"
                                            data-unit-price="{{ (float) $item->unit_price }}"
                                            data-qty="{{ (float) $item->qty }}"
                                            {{ $isBpjs ? 'style=display:none;' : '' }}>
                                        Gratis
                                    </button>
                                </div>
                            </td>

                            <td class="right" style="font-weight:900;">
                                {{ format_rupiah($item->subtotal) }}
                                @if($itemDiscount > 0)
                                    <div class="muted" style="font-size:12px;margin-top:4px;">
                                        Diskon: {{ format_rupiah($itemDiscount) }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="item-edit-actions">
                                    <form id="{{ $formId }}"
                                          method="POST"
                                          action="{{ route('income.items.update', [$incomeTransaction->id, $item->id]) }}"
                                          style="display:inline-block;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-primary" style="padding:8px 14px;">Update</button>
                                    </form>

                                    <form method="POST"
                                          action="{{ route('income.items.destroy', [$incomeTransaction->id, $item->id]) }}"
                                          onsubmit="return confirm('Hapus item ini?')"
                                          style="display:inline-block;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding:8px 14px;">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="padding:18px 16px;text-align:center;color:#6b7280;">
                                Belum ada item tindakan. Tambahkan minimal 1 tindakan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="sectionPembayaran">
        <div class="card">
            <div class="card-h">
                <h2 style="font-size:18px;font-weight:900;margin:0;">Pembayaran</h2>
                <div class="muted">
                    Tagihan: <span style="font-weight:900;color:#111827;">{{ format_rupiah($incomeTransaction->bill_total) }}</span>
                    <span style="margin:0 10px;">•</span>
                    Dibayar: <span style="font-weight:900;color:#111827;">{{ format_rupiah($incomeTransaction->pay_total) }}</span>
                    <span style="margin:0 10px;">•</span>
                    Sisa: <span style="font-weight:900;color:#111827;">{{ format_rupiah($remainingTotal) }}</span>
                </div>
            </div>

            <div class="card-b" style="position:relative;" id="paymentWrapper">
                <div id="paymentLockOverlay" style="
                    position:absolute;
                    inset:0;
                    background:rgba(255,255,255,0.85);
                    display:none;
                    align-items:center;
                    justify-content:center;
                    text-align:center;
                    z-index:10;
                    border-radius:12px;
                ">
                    <div style="max-width:320px;padding:20px;">
                        <div style="font-size:20px;font-weight:900;margin-bottom:8px;">
                            ⚠️ Belum Ada Tagihan
                        </div>
                        <div style="font-size:14px;color:#555;line-height:1.5;">
                            Tambahkan dan simpan tindakan terlebih dahulu sebelum melakukan pembayaran.
                        </div>
                    </div>
                </div>

                @if($isBpjs)
                    @php
                        $payDateValue = old('pay_date', now()->format('Y-m-d'));
                    @endphp

                    <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 text-blue-800" style="margin-bottom:14px;">
                        Transaksi <b>BPJS</b> tetap memakai tombol <b>Bayar</b> agar alur admin tetap konsisten, tetapi sistem akan menyimpan transaksi ini dengan nilai pembayaran <b>Rp0</b>.
                    </div>

                    <form method="POST" action="{{ route('income.pay', $incomeTransaction->id) }}" class="grid2">
                        @csrf

                        <div>
                            <label class="label">Kategori Pembayaran</label>
                            <input type="text" class="input" value="BPJS (otomatis Rp0)" readonly>
                        </div>

                        <div>
                            <label class="label">Tanggal Simpan</label>
                            <input type="date" name="pay_date" class="input" value="{{ $payDateValue }}">
                        </div>

                        <div style="grid-column:1/-1;">
                            <label class="label">Jumlah (Rp)</label>
                            <input type="text" class="input" value="0" readonly>
                        </div>

                        <div style="grid-column:1/-1;display:flex;justify-content:flex-end;">
                            <button type="submit"
                                    onclick="return confirm('Simpan transaksi BPJS dengan nilai pembayaran Rp0?')"
                                    class="btn btn-primary">
                                Bayar
                            </button>
                        </div>
                    </form>
                @else
                    @if($isPaidTransaction)
                        <div class="rounded-lg bg-green-50 border border-green-200 p-4 text-green-800" style="margin-bottom:14px;">
                            Transaksi sudah <b>PAID</b>. Riwayat pembayaran tetap tampil untuk audit multi payment.
                        </div>
                    @endif

                    @if(!$isPaidTransaction)
                        @php
                            $payDateValue = old('pay_date', now()->format('Y-m-d'));
                        @endphp

                        @if($showZeroBillKhususConfirmation)
                            <div style="background:#fff7ed;border:1px solid #fdba74;border-radius:12px;padding:12px 14px;margin-bottom:14px;color:#9a3412;">
                                <b>Kategori KHUSUS aktif.</b> Karena total akhir sudah <b>Rp0</b>, admin bisa langsung klik <b>Bayar</b> untuk konfirmasi transaksi gratis tanpa metode pembayaran.
                            </div>
                        @elseif($isKhusus)
                            <div style="background:#fff7ed;border:1px solid #fdba74;border-radius:12px;padding:12px 14px;margin-bottom:14px;color:#9a3412;">
                                <b>Kategori KHUSUS aktif.</b> Diskon item sudah mengurangi total tagihan, tetapi karena masih ada sisa tagihan, metode pembayaran tetap harus diisi.
                            </div>
                        @else
                            <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:12px;padding:12px 14px;margin-bottom:14px;">
                                <b>Multi payment aktif.</b> Isi beberapa metode pembayaran sekaligus bila perlu.
                                Contoh:
                                <b>Tunai Rp300.000</b> + <b>BCA Transfer Rp700.000</b>.
                                Sistem akan menyimpan semua baris yang terisi dan menjumlahkannya otomatis.
                            </div>
                        @endif

                        <form method="POST" action="{{ route('income.pay', $incomeTransaction->id) }}" id="payForm">
                            @csrf

                            @if($showPaymentMethodSection)
                                <div class="grid2 mb20">
                                    <div>
                                        <label class="label">Tanggal Bayar</label>
                                        <input type="date" name="pay_date" class="input" value="{{ $payDateValue }}">
                                    </div>
                                    <div>
                                        <label class="label">Catatan Form</label>
                                        <div class="preview-box" style="height:100%;">
                                            Isi hanya baris yang dipakai.
                                            <div class="muted" style="font-size:12px;margin-top:6px;">
                                                TUNAI akan otomatis masuk channel <b>CASH</b>. Untuk bank wajib pilih channel <b>TRANSFER / EDC / QRIS</b>.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="paymentRowsWrapper">
                                    @for($i = 0; $i < $paymentRowCount; $i++)
                                        @php
                                            $rowMethod = $oldMethodIds[$i] ?? '';
                                            $rowChannel = strtoupper((string) ($oldChannels[$i] ?? 'TRANSFER'));
                                            $rowAmount = $oldAmounts[$i] ?? '';
                                        @endphp
                                        <div class="payment-row payment-row-item">
                                            <div class="payment-row-grid">
                                                <div>
                                                    <label class="label">Metode Bayar</label>
                                                    <select name="payment_method_id[]" class="select payment-method-select">
                                                        <option value="">- Pilih metode -</option>
                                                        @foreach($paymentMethods as $pm)
                                                            <option value="{{ $pm->id }}"
                                                                    data-name="{{ strtoupper($pm->name) }}"
                                                                    @selected((string) $rowMethod === (string) $pm->id)>
                                                                {{ $pm->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="muted" style="font-size:12px;margin-top:6px;">
                                                        Contoh: Tunai / BCA / BNI / BRI
                                                    </div>
                                                </div>

                                                <div class="payment-channel-wrap">
                                                    <label class="label">Channel</label>
                                                    <select name="channel[]" class="select payment-channel-select">
                                                        <option value="TRANSFER" @selected($rowChannel === 'TRANSFER')>TRANSFER</option>
                                                        <option value="EDC" @selected($rowChannel === 'EDC')>EDC</option>
                                                        <option value="QRIS" @selected($rowChannel === 'QRIS')>QRIS</option>
                                                    </select>
                                                    <div class="muted" style="font-size:12px;margin-top:6px;">
                                                        Akan masuk ke kolom sesuai metode + channel.
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="label">Jumlah (Rp)</label>
                                                    <input type="text"
                                                           name="amount[]"
                                                           class="input rupiah-input payment-amount-input"
                                                           value="{{ $rowAmount }}"
                                                           placeholder="contoh: 150.000">
                                                </div>

                                                <div style="display:flex;justify-content:flex-end;align-items:end;">
                                                    <button type="button" class="btn btn-outline remove-payment-row">Hapus Baris</button>
                                                </div>
                                            </div>
                                        </div>
                                    @endfor
                                </div>

                                <div class="d-flex gap-2" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px;">
                                    <button type="button" id="addPaymentRowBtn" class="btn btn-secondary">
                                        + Tambah Metode Pembayaran
                                    </button>
                                    <button type="button" id="btnBayarPas" class="btn btn-primary">
                                        ⚡ Bayar Pas
                                    </button>
                                </div>

                                <div id="payment_status_box" class="payment-status-box info">
                                    <div id="payment_status_text" style="font-weight:900;">-</div>
                                </div>

                                <div class="preview-box" style="margin-bottom:16px;">
                                    <div class="preview-row">
                                        <span class="muted">Total Tagihan</span>
                                        <b>{{ format_rupiah($billTotalBase) }}</b>
                                    </div>
                                    <div class="preview-row" style="margin-top:6px;">
                                        <span class="muted">Sudah Dibayar Sebelumnya</span>
                                        <b id="already_paid_preview">{{ format_rupiah($paidTotal) }}</b>
                                    </div>
                                    <div class="preview-row" style="margin-top:6px;">
                                        <span class="muted">Total Pembayaran Baru</span>
                                        <b id="current_payment_preview">Rp 0</b>
                                    </div>
                                    <div class="preview-row" style="margin-top:6px;">
                                        <span class="muted">Preview Total Setelah Simpan</span>
                                        <b id="next_paid_total_preview">{{ format_rupiah($paidTotal) }}</b>
                                    </div>
                                    <div class="preview-row" style="margin-top:6px;">
                                        <span class="muted">Preview Sisa Setelah Simpan</span>
                                        <b id="next_remaining_preview">{{ format_rupiah($remainingTotal) }}</b>
                                    </div>
                                </div>
                            @else
                                <div class="preview-box" style="margin-bottom:16px;">
                                    <div class="preview-row">
                                        <span class="muted">Total Tagihan</span>
                                        <b>{{ format_rupiah($billTotalBase) }}</b>
                                    </div>
                                    <div class="preview-row" style="margin-top:6px;">
                                        <span class="muted">Sudah Dibayar</span>
                                        <b>{{ format_rupiah($paidTotal) }}</b>
                                    </div>
                                    <div class="muted" style="font-size:12px;margin-top:6px;">
                                        Untuk kategori <b>KHUSUS</b> dengan total akhir <b>Rp0</b>, tombol <b>Bayar</b> berfungsi sebagai konfirmasi transaksi gratis.
                                    </div>
                                </div>
                            @endif

                            <div style="display:flex;justify-content:flex-end;">
                                <button type="submit"
                                        id="submitPayBtn"
                                        onclick="return confirm('{{ $showZeroBillKhususConfirmation ? 'Konfirmasi transaksi KHUSUS gratis ini? Jika tindakan sudah tersimpan, status akan menjadi PAID.' : 'Simpan semua pembayaran yang terisi? Jika total sudah sama dengan tagihan maka status menjadi PAID.' }}')"
                                        class="btn btn-primary">
                                    Bayar
                                </button>
                            </div>
                        </form>
                    @endif
                @endif

                <div style="margin-top:18px;">
                    <h3 style="font-size:16px;font-weight:900;margin:0 0 10px 0;">Riwayat Pembayaran</h3>

                    @php
                        $hasPayments = isset($payments) && count($payments) > 0;
                    @endphp

                    @if(!$hasPayments)
                        <div class="muted" style="padding:12px 0;">Belum ada pembayaran.</div>
                    @else
                        <div style="overflow-x:auto;">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th style="width:180px;">Tanggal</th>
                                        <th>Metode (Bank)</th>
                                        <th style="width:140px;">Channel</th>
                                        <th class="right" style="width:180px;">Jumlah</th>
                                        <th style="width:160px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $p)
                                        @php
                                            $amtText = number_format((float) $p->amount, 0, ',', '.');
                                            $confirmMsg = "Hapus pembayaran ini?\n\nMetode: {$p->method_name}\nChannel: " . strtoupper((string) $p->channel) . "\nJumlah: {$amtText}\n\nCatatan: Setelah dihapus, pay_total & status akan dihitung ulang otomatis.";
                                        @endphp
                                        <tr>
                                            <td>{{ function_exists('tgl_id') ? tgl_id($p->pay_date, 'd F Y') : \Carbon\Carbon::parse($p->pay_date)->format('Y-m-d') }}</td>
                                            <td style="font-weight:900;">{{ $p->method_name }}</td>
                                            <td>{{ strtoupper($p->channel) }}</td>
                                            <td class="right" style="font-weight:900;">{{ format_rupiah($p->amount) }}</td>
                                            <td>
                                                <form method="POST"
                                                      action="{{ route('income.payments.destroy', [$incomeTransaction->id, $p->id]) }}"
                                                      onsubmit="return confirm(@json($confirmMsg));"
                                                      style="display:inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger" style="padding:8px 14px;">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="muted" style="font-size:12px;margin-top:8px;">
                        * Riwayat ini untuk audit multi payment. Hapus payment akan recalculation otomatis. Arus kas akan masuk ke laporan sesuai metode dan channel masing-masing.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="paymentRowTemplate">
        <div class="payment-row payment-row-item">
            <div class="payment-row-grid">
                <div>
                    <label class="label">Metode Bayar</label>
                    <select name="payment_method_id[]" class="select payment-method-select">
                        <option value="">- Pilih metode -</option>
                        @foreach($paymentMethods as $pm)
                            <option value="{{ $pm->id }}" data-name="{{ strtoupper($pm->name) }}">
                                {{ $pm->name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="muted" style="font-size:12px;margin-top:6px;">
                        Contoh: Tunai / BCA / BNI / BRI
                    </div>
                </div>

                <div class="payment-channel-wrap">
                    <label class="label">Channel</label>
                    <select name="channel[]" class="select payment-channel-select">
                        <option value="TRANSFER">TRANSFER</option>
                        <option value="EDC">EDC</option>
                        <option value="QRIS">QRIS</option>
                    </select>
                    <div class="muted" style="font-size:12px;margin-top:6px;">
                        Akan masuk ke kolom sesuai metode + channel.
                    </div>
                </div>

                <div>
                    <label class="label">Jumlah (Rp)</label>
                    <input type="text" name="amount[]" class="input rupiah-input payment-amount-input" placeholder="contoh: 150.000">
                </div>

                <div style="display:flex;justify-content:flex-end;align-items:end;">
                    <button type="button" class="btn btn-outline remove-payment-row">Hapus Baris</button>
                </div>
            </div>
        </div>
    </template>

    <script>
        (function () {
            function toDigits(str) {
                return (str || '').toString().replace(/[^\d]/g, '');
            }

            function formatId(strOrNumber) {
                const digits = toDigits(strOrNumber);
                if (!digits) return '';
                return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }

            function attachRupiahFormatting(el) {
                if (!el) return;
                el.value = formatId(el.value);

                el.addEventListener('input', function () {
                    el.value = formatId(el.value);
                    try { el.setSelectionRange(el.value.length, el.value.length); } catch (e) {}
                    updatePaymentPreview();
                    updatePreview();
                });

                el.addEventListener('blur', function () {
                    el.value = formatId(el.value);
                    updatePaymentPreview();
                    updatePreview();
                });
            }

            function formatRpLabel(num) {
                const n = Math.max(0, Math.round((Number(num) || 0)));
                const s = n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
                return 'Rp ' + s;
            }

            function parseRupiahToNumber(str) {
                const digits = toDigits(str);
                if (!digits) return 0;
                return Number(digits);
            }

            const isBpjs = @json($isBpjs);
            const isKhusus = @json($isKhusus);
            const isKhususZeroBill = @json($isKhususZeroBill);
            const isPaidTransaction = @json($isPaidTransaction);
            const showPaymentMethodSection = @json($showPaymentMethodSection);
            const billTotalBase = Number(@json($billTotalBase));
            const alreadyPaidBase = Number(@json($paidTotal));

            const qtyEl = document.getElementById('qty');
            const unitPriceEl = document.getElementById('unit_price');
            const discountAmountEl = document.getElementById('discount_amount');

            const previewGrossSubtotalEl = document.getElementById('preview_gross_subtotal');
            const previewDiscountEl = document.getElementById('preview_discount');
            const previewSubtotalEl = document.getElementById('preview_subtotal');
            const previewTotalNewEl = document.getElementById('preview_total_new');

            const treatmentSelectEl = document.getElementById('treatment_id');
            const treatmentModeInfoEl = document.getElementById('treatment_mode_info');
            const treatmentModeTitleEl = document.getElementById('treatment_mode_title');
            const treatmentModeDescEl = document.getElementById('treatment_mode_desc');
            const notesHintBoxEl = document.getElementById('notes_hint_box');
            const notesHintTextEl = document.getElementById('notes_hint_text');
            const unitPriceHelpEl = document.getElementById('unit_price_help');
            const freeTreatmentNoticeEl = document.getElementById('free_treatment_notice');

            const currentPaymentPreviewEl = document.getElementById('current_payment_preview');
            const nextPaidTotalPreviewEl = document.getElementById('next_paid_total_preview');
            const nextRemainingPreviewEl = document.getElementById('next_remaining_preview');

            const paymentRowsWrapper = document.getElementById('paymentRowsWrapper');
            const paymentRowTemplate = document.getElementById('paymentRowTemplate');
            const addPaymentRowBtn = document.getElementById('addPaymentRowBtn');
            const btnBayarPas = document.getElementById('btnBayarPas');
            const paymentStatusBoxEl = document.getElementById('payment_status_box');
            const paymentStatusTextEl = document.getElementById('payment_status_text');
            const submitPayBtn = document.getElementById('submitPayBtn');
            const paymentLockOverlay = document.getElementById('paymentLockOverlay');

            const orthoAutoNoticeWrapEl = document.getElementById('ortho_auto_notice_wrap');
            const orthoCaseModeInlineWrapEl = document.getElementById('ortho_case_mode_inline_wrap');
            const orthoCaseModeSelectInlineEl = document.getElementById('ortho_case_mode_select_inline');
            const prostoAutoNoticeWrapEl = document.getElementById('prosto_auto_notice_wrap');
            const prostoCaseModeInlineWrapEl = document.getElementById('prosto_case_mode_inline_wrap');
            const prostoCaseModeSelectInlineEl = document.getElementById('prosto_case_mode_select_inline');

            const addItemAnchorEl = document.getElementById('add-item-form');
            const addItemFormEl = document.getElementById('addItemForm');
            const freeTreatmentBtnEl = document.getElementById('freeTreatmentBtn');

            function updatePreview() {
                if (!qtyEl || !unitPriceEl || !discountAmountEl) return;

                const qty = Number(qtyEl.value || 0);
                const unitPrice = isBpjs ? 0 : parseRupiahToNumber(unitPriceEl.value || '0');
                const grossSubtotal = (qty > 0 ? qty : 0) * (unitPrice > 0 ? unitPrice : 0);

                let discount = isBpjs ? 0 : parseRupiahToNumber(discountAmountEl.value || '0');
                if (discount > grossSubtotal) {
                    discount = grossSubtotal;
                    discountAmountEl.value = formatId(discount);
                }

                const subtotal = isBpjs ? 0 : Math.max(0, grossSubtotal - discount);
                const totalNew = isBpjs ? 0 : (billTotalBase + subtotal);

                if (previewGrossSubtotalEl) previewGrossSubtotalEl.textContent = formatRpLabel(grossSubtotal);
                if (previewDiscountEl) previewDiscountEl.textContent = formatRpLabel(discount);
                if (previewSubtotalEl) previewSubtotalEl.textContent = formatRpLabel(subtotal);
                if (previewTotalNewEl) previewTotalNewEl.textContent = formatRpLabel(totalNew);
            }

            function getCurrentPaymentAmount() {
                if (!paymentRowsWrapper) return 0;
                let currentPayment = 0;
                paymentRowsWrapper.querySelectorAll('.payment-amount-input').forEach(function (el) {
                    currentPayment += parseRupiahToNumber(el.value || '0');
                });
                return currentPayment;
            }

            function updatePaymentStatusBox() {
                if (!paymentStatusBoxEl || !paymentStatusTextEl || !submitPayBtn) return;

                if (isBpjs || !showPaymentMethodSection) {
                    paymentStatusBoxEl.style.display = 'none';
                    submitPayBtn.disabled = false;
                    return;
                }

                const currentPayment = getCurrentPaymentAmount();
                const targetRemaining = Math.max(0, billTotalBase - alreadyPaidBase);
                const diff = targetRemaining - currentPayment;

                paymentStatusBoxEl.style.display = '';

                if (currentPayment <= 0) {
                    paymentStatusBoxEl.className = 'payment-status-box info';
                    paymentStatusTextEl.textContent = 'Belum ada nominal pembayaran baru yang diisi.';
                    submitPayBtn.disabled = true;
                    return;
                }

                if (diff > 0) {
                    paymentStatusBoxEl.className = 'payment-status-box danger';
                    paymentStatusTextEl.textContent = 'Sisa belum dibayar: ' + formatRpLabel(diff) + '. Tombol Bayar dikunci sampai total pas.';
                    submitPayBtn.disabled = true;
                    return;
                }

                if (diff < 0) {
                    paymentStatusBoxEl.className = 'payment-status-box danger';
                    paymentStatusTextEl.textContent = 'Nominal pembayaran lebih besar ' + formatRpLabel(Math.abs(diff)) + ' dari sisa tagihan. Periksa kembali.';
                    submitPayBtn.disabled = true;
                    return;
                }

                paymentStatusBoxEl.className = 'payment-status-box success';
                paymentStatusTextEl.textContent = 'Total pembayaran sudah pas. Siap disimpan.';
                submitPayBtn.disabled = false;
            }

            function updatePaymentPreview() {
                if (!paymentRowsWrapper) return;

                const currentPayment = getCurrentPaymentAmount();
                const nextPaid = alreadyPaidBase + currentPayment;
                const nextRemaining = Math.max(0, billTotalBase - nextPaid);

                if (currentPaymentPreviewEl) currentPaymentPreviewEl.textContent = formatRpLabel(currentPayment);
                if (nextPaidTotalPreviewEl) nextPaidTotalPreviewEl.textContent = formatRpLabel(nextPaid);
                if (nextRemainingPreviewEl) nextRemainingPreviewEl.textContent = formatRpLabel(nextRemaining);

                updatePaymentStatusBox();
            }

            function updatePaymentLock() {
                if (!paymentLockOverlay) return;

                if (billTotalBase > 0 || isBpjs || isPaidTransaction) {
                    paymentLockOverlay.style.display = 'none';
                } else {
                    paymentLockOverlay.style.display = 'flex';
                }
            }

            function isSelectedTreatmentOrtho() {
                if (!treatmentSelectEl) return false;
                const opt = treatmentSelectEl.options[treatmentSelectEl.selectedIndex];
                if (!opt || !opt.value) return false;
                return (opt.getAttribute('data-is-ortho') || '0') === '1';
            }

            function isSelectedTreatmentProsto() {
                if (!treatmentSelectEl) return false;
                const opt = treatmentSelectEl.options[treatmentSelectEl.selectedIndex];
                if (!opt || !opt.value) return false;
                return (opt.getAttribute('data-is-prosto') || '0') === '1';
            }

            function syncOrthoCaseModeVisibility() {
                const isOrtho = isSelectedTreatmentOrtho();

                if (orthoAutoNoticeWrapEl) {
                    orthoAutoNoticeWrapEl.style.display = isOrtho ? '' : 'none';
                }

                if (orthoCaseModeInlineWrapEl) {
                    orthoCaseModeInlineWrapEl.style.display = isOrtho ? '' : 'none';
                }

                if (!isOrtho && orthoCaseModeSelectInlineEl) {
                    orthoCaseModeSelectInlineEl.value = 'none';
                }
            }

            function syncProstoVisibility() {
                const isProsto = isSelectedTreatmentProsto();

                if (prostoAutoNoticeWrapEl) {
                    prostoAutoNoticeWrapEl.style.display = isProsto ? '' : 'none';
                }

                if (prostoCaseModeInlineWrapEl) {
                    prostoCaseModeInlineWrapEl.style.display = isProsto ? '' : 'none';
                }

                if (!isProsto && prostoCaseModeSelectInlineEl) {
                    prostoCaseModeSelectInlineEl.value = 'none';
                }
            }

            function syncTreatmentPriceUI() {
                if (!treatmentSelectEl || !unitPriceEl || !discountAmountEl) return;

                const opt = treatmentSelectEl.options[treatmentSelectEl.selectedIndex];
                const selectedValue = opt ? (opt.value || '') : '';

                if (!selectedValue) {
                    if (!isBpjs) {
                        unitPriceEl.removeAttribute('readonly');
                        unitPriceEl.value = '';
                        discountAmountEl.removeAttribute('readonly');
                        discountAmountEl.value = '0';
                    } else {
                        unitPriceEl.value = '0';
                        unitPriceEl.setAttribute('readonly', 'readonly');
                        discountAmountEl.value = '0';
                        discountAmountEl.setAttribute('readonly', 'readonly');
                    }

                    if (treatmentModeInfoEl) treatmentModeInfoEl.style.display = 'none';
                    if (notesHintBoxEl) notesHintBoxEl.style.display = 'none';
                    if (freeTreatmentNoticeEl) freeTreatmentNoticeEl.style.display = 'none';
                    if (unitPriceHelpEl) {
                        unitPriceHelpEl.textContent = isBpjs
                            ? 'Karena BPJS, harga otomatis 0.'
                            : 'Pilih tindakan terlebih dahulu.';
                    }

                    syncOrthoCaseModeVisibility();
                    syncProstoVisibility();
                    updatePreview();
                    return;
                }

                const price = opt.getAttribute('data-price') || '0';
                const priceMode = (opt.getAttribute('data-price-mode') || 'fixed').toLowerCase();
                const notesHint = opt.getAttribute('data-notes-hint') || '';
                const treatmentName = opt.text || 'Treatment';
                const isFreeTreatment = (opt.getAttribute('data-is-free') || '0') === '1';
                const isOrthoTreatment = (opt.getAttribute('data-is-ortho') || '0') === '1';
                const isProstoTreatment = (opt.getAttribute('data-is-prosto') || '0') === '1';

                if (freeTreatmentNoticeEl) {
                    freeTreatmentNoticeEl.style.display = 'none';
                }

                if (isBpjs) {
                    unitPriceEl.value = '0';
                    unitPriceEl.setAttribute('readonly', 'readonly');
                    discountAmountEl.value = '0';
                    discountAmountEl.setAttribute('readonly', 'readonly');

                    if (unitPriceHelpEl) {
                        unitPriceHelpEl.textContent = 'Karena BPJS, harga otomatis 0.';
                    }
                } else if (isFreeTreatment) {
                    unitPriceEl.value = '0';
                    unitPriceEl.setAttribute('readonly', 'readonly');
                    unitPriceEl.placeholder = 'harga otomatis 0';
                    discountAmountEl.removeAttribute('readonly');
                    discountAmountEl.value = '0';

                    if (unitPriceHelpEl) {
                        unitPriceHelpEl.textContent = 'Treatment gratis: harga otomatis 0 dan tetap bisa diproses sebagai pasien UMUM.';
                    }

                    if (freeTreatmentNoticeEl) {
                        freeTreatmentNoticeEl.style.display = 'block';
                    }
                } else if (priceMode === 'manual') {
                    unitPriceEl.removeAttribute('readonly');
                    if (!unitPriceEl.value || parseRupiahToNumber(unitPriceEl.value) <= 0) {
                        unitPriceEl.value = '';
                    }
                    unitPriceEl.placeholder = 'isi harga final manual';
                    discountAmountEl.removeAttribute('readonly');

                    if (unitPriceHelpEl) {
                        unitPriceHelpEl.textContent = 'Mode manual: harga final wajib diisi sesuai kasus pasien.';
                    }
                } else {
                    unitPriceEl.value = formatId(price);
                    unitPriceEl.setAttribute('readonly', 'readonly');
                    unitPriceEl.placeholder = 'harga otomatis dari master';
                    discountAmountEl.removeAttribute('readonly');

                    if (unitPriceHelpEl) {
                        unitPriceHelpEl.textContent = 'Mode fixed: harga otomatis mengikuti Master Tindakan.';
                    }
                }

                if (treatmentModeInfoEl && treatmentModeTitleEl && treatmentModeDescEl) {
                    treatmentModeInfoEl.style.display = '';

                    if (isFreeTreatment) {
                        treatmentModeInfoEl.className = 'fixed-box';
                        treatmentModeTitleEl.textContent = 'Treatment Gratis';
                        treatmentModeDescEl.textContent = treatmentName + ' ditandai sebagai FREE. Harga otomatis 0 dan tetap bisa dicatat untuk pasien UMUM.';
                    } else if (priceMode === 'manual') {
                        treatmentModeInfoEl.className = 'manual-box';
                        treatmentModeTitleEl.textContent = 'Mode Harga Manual';
                        treatmentModeDescEl.textContent = treatmentName + ' menggunakan harga final manual yang diisi saat transaksi.';
                    } else {
                        treatmentModeInfoEl.className = 'fixed-box';
                        treatmentModeTitleEl.textContent = 'Mode Harga Tetap';
                        treatmentModeDescEl.textContent = treatmentName + ' menggunakan harga otomatis dari Master Tindakan.';
                    }

                    if (isOrthoTreatment) {
                        treatmentModeDescEl.textContent += ' Treatment ini ditandai sebagai terkait ORTHO.';
                    }
                    if (isProstoTreatment) {
                        treatmentModeDescEl.textContent += ' Treatment ini ditandai sebagai terkait PROSTO.';
                    }
                }

                if (notesHintBoxEl && notesHintTextEl) {
                    if (notesHint.trim() !== '') {
                        notesHintBoxEl.style.display = '';
                        notesHintTextEl.textContent = notesHint;
                    } else {
                        notesHintBoxEl.style.display = 'none';
                        notesHintTextEl.textContent = '-';
                    }
                }

                syncOrthoCaseModeVisibility();
                syncProstoVisibility();
                updatePreview();
            }

            function syncPaymentRowChannel(rowEl) {
                if (!rowEl) return;

                const methodSelect = rowEl.querySelector('.payment-method-select');
                const channelWrap = rowEl.querySelector('.payment-channel-wrap');
                const channelSelect = rowEl.querySelector('.payment-channel-select');

                if (!methodSelect || !channelWrap || !channelSelect) return;

                const opt = methodSelect.options[methodSelect.selectedIndex];
                const name = opt ? (opt.getAttribute('data-name') || '') : '';
                const upper = (name || '').toUpperCase().trim();
                const isCash = (upper === 'TUNAI' || upper === '');

                if (isCash) {
                    channelWrap.style.display = 'none';
                    channelSelect.innerHTML = '<option value="CASH" selected>CASH</option>';
                    channelSelect.value = 'CASH';
                } else {
                    channelWrap.style.display = 'block';

                    if (!channelSelect.querySelector('option[value="TRANSFER"]')) {
                        channelSelect.innerHTML = `
                            <option value="TRANSFER">TRANSFER</option>
                            <option value="EDC">EDC</option>
                            <option value="QRIS">QRIS</option>
                        `;
                    }

                    if (!['TRANSFER', 'EDC', 'QRIS'].includes((channelSelect.value || '').toUpperCase())) {
                        channelSelect.value = 'TRANSFER';
                    }
                }
            }

            function focusAmountInput(rowEl) {
                if (!rowEl) return;
                const amountInput = rowEl.querySelector('.payment-amount-input');
                if (!amountInput) return;
                setTimeout(function () {
                    amountInput.focus();
                    amountInput.select && amountInput.select();
                }, 80);
            }

            function focusMethodSelect(rowEl) {
                if (!rowEl) return;
                const methodSelect = rowEl.querySelector('.payment-method-select');
                if (!methodSelect) return;
                setTimeout(function () {
                    methodSelect.focus();
                }, 80);
            }

            function bindPaymentRow(rowEl) {
                if (!rowEl) return;

                rowEl.querySelectorAll('.rupiah-input').forEach(attachRupiahFormatting);

                const methodSelect = rowEl.querySelector('.payment-method-select');
                const amountInput = rowEl.querySelector('.payment-amount-input');
                const removeBtn = rowEl.querySelector('.remove-payment-row');

                if (methodSelect) {
                    methodSelect.addEventListener('change', function () {
                        syncPaymentRowChannel(rowEl);
                        if (methodSelect.value) {
                            focusAmountInput(rowEl);
                        }
                    });
                }

                if (amountInput) {
                    amountInput.addEventListener('input', updatePaymentPreview);
                    amountInput.addEventListener('blur', updatePaymentPreview);

                    amountInput.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            submitPayBtn && submitPayBtn.focus();
                        }
                    });
                }

                if (removeBtn) {
                    removeBtn.addEventListener('click', function () {
                        const rows = paymentRowsWrapper ? paymentRowsWrapper.querySelectorAll('.payment-row-item') : [];
                        if (rows.length <= 1) {
                            const methodSelectEl = rowEl.querySelector('.payment-method-select');
                            const channelSelectEl = rowEl.querySelector('.payment-channel-select');
                            const amountInputEl = rowEl.querySelector('.payment-amount-input');

                            if (methodSelectEl) methodSelectEl.value = '';
                            if (channelSelectEl) {
                                channelSelectEl.innerHTML = `
                                    <option value="TRANSFER">TRANSFER</option>
                                    <option value="EDC">EDC</option>
                                    <option value="QRIS">QRIS</option>
                                `;
                                channelSelectEl.value = 'TRANSFER';
                            }
                            if (amountInputEl) amountInputEl.value = '';
                            syncPaymentRowChannel(rowEl);
                            updatePaymentPreview();
                            focusMethodSelect(rowEl);
                            return;
                        }

                        rowEl.remove();
                        updatePaymentPreview();

                        const lastRow = paymentRowsWrapper ? paymentRowsWrapper.lastElementChild : null;
                        if (lastRow) focusMethodSelect(lastRow);
                    });
                }

                syncPaymentRowChannel(rowEl);
            }

            function scrollToAddItemArea() {
                if (!addItemAnchorEl) return;
                addItemAnchorEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            function focusTreatmentField() {
                if (!treatmentSelectEl) return;
                setTimeout(function () {
                    treatmentSelectEl.focus();
                }, 350);
            }

            function handlePostAddItemFlow() {
                const hash = window.location.hash || '';
                const isAddItemHash = hash === '#add-item-form';
                const hasSuccessMessage = @json(session('success'));
                const hasAddItemSuccess = typeof hasSuccessMessage === 'string'
                    && hasSuccessMessage.toLowerCase().includes('item tindakan berhasil ditambahkan');

                if (isAddItemHash || hasAddItemSuccess) {
                    scrollToAddItemArea();
                    focusTreatmentField();
                }
            }

            function handleAfterAddItem() {
                const hasSuccessMessage = @json(session('success'));
                const hasAddItemSuccess = typeof hasSuccessMessage === 'string'
                    && hasSuccessMessage.toLowerCase().includes('item tindakan berhasil ditambahkan');

                if (!hasAddItemSuccess) return;

                setTimeout(function () {
                    scrollToAddItemArea();
                    focusTreatmentField();
                }, 250);
            }

            function handleBayarPas() {
                if (!btnBayarPas || !paymentRowsWrapper) return;

                btnBayarPas.addEventListener('click', function () {
                    const rows = Array.from(paymentRowsWrapper.querySelectorAll('.payment-row-item'));
                    if (!rows.length) return;

                    let formCurrentTotal = 0;
                    let firstAmountInput = null;
                    let firstEmptyAmountInput = null;

                    rows.forEach(function (row) {
                        const amountInput = row.querySelector('.payment-amount-input');
                        if (!amountInput) return;

                        if (!firstAmountInput) {
                            firstAmountInput = amountInput;
                        }

                        const value = parseRupiahToNumber(amountInput.value || '0');

                        if (value > 0) {
                            formCurrentTotal += value;
                        } else if (!firstEmptyAmountInput) {
                            firstEmptyAmountInput = amountInput;
                        }
                    });

                    const remainingAfterManual = Math.max(0, billTotalBase - alreadyPaidBase - formCurrentTotal);

                    if (remainingAfterManual <= 0) {
                        alert('Sisa tagihan sudah Rp0. Tidak ada nominal yang perlu diisi.');
                        updatePaymentPreview();
                        return;
                    }

                    const hasAnyManualInput = formCurrentTotal > 0;

                    if (!hasAnyManualInput && firstAmountInput) {
                        firstAmountInput.value = formatId(remainingAfterManual);
                        updatePaymentPreview();
                        setTimeout(function () {
                            firstAmountInput.focus();
                            firstAmountInput.select && firstAmountInput.select();
                        }, 50);
                        return;
                    }

                    if (firstEmptyAmountInput) {
                        firstEmptyAmountInput.value = formatId(remainingAfterManual);
                        updatePaymentPreview();
                        setTimeout(function () {
                            firstEmptyAmountInput.focus();
                            firstEmptyAmountInput.select && firstEmptyAmountInput.select();
                        }, 50);
                        return;
                    }

                    alert('Semua kolom pembayaran sudah terisi. Kosongkan satu kolom jika ingin mengisi sisa otomatis.');
                    updatePaymentPreview();
                });
            }

            document.querySelectorAll('.rupiah-input').forEach(attachRupiahFormatting);

            if (treatmentSelectEl) {
                treatmentSelectEl.addEventListener('change', function () {
                    syncTreatmentPriceUI();

                    setTimeout(function () {
                        if (orthoCaseModeInlineWrapEl && orthoCaseModeInlineWrapEl.style.display !== 'none' && orthoCaseModeSelectInlineEl) {
                            orthoCaseModeSelectInlineEl.focus();
                            return;
                        }

                        if (qtyEl) {
                            qtyEl.focus();
                            qtyEl.select && qtyEl.select();
                        }
                    }, 100);
                });
            }

            if (qtyEl) {
                qtyEl.addEventListener('input', updatePreview);

                qtyEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();

                        if (unitPriceEl && !unitPriceEl.hasAttribute('readonly')) {
                            unitPriceEl.focus();
                            unitPriceEl.select && unitPriceEl.select();
                            return;
                        }

                        if (discountAmountEl) {
                            discountAmountEl.focus();
                            discountAmountEl.select && discountAmountEl.select();
                        }
                    }
                });
            }

            if (unitPriceEl) {
                unitPriceEl.addEventListener('input', updatePreview);

                unitPriceEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        if (discountAmountEl) {
                            discountAmountEl.focus();
                            discountAmountEl.select && discountAmountEl.select();
                        }
                    }
                });
            }

            if (discountAmountEl) {
                discountAmountEl.addEventListener('input', updatePreview);
                discountAmountEl.addEventListener('blur', updatePreview);

                discountAmountEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const submitBtn = addItemFormEl ? addItemFormEl.querySelector('button[type="submit"]') : null;
                        if (submitBtn) submitBtn.focus();
                    }
                });
            }

            if (freeTreatmentBtnEl) {
                freeTreatmentBtnEl.addEventListener('click', function () {
                    const qty = Number(qtyEl ? qtyEl.value || 0 : 0);
                    const unitPrice = parseRupiahToNumber(unitPriceEl ? unitPriceEl.value || '0' : '0');
                    const gross = Math.max(0, qty) * Math.max(0, unitPrice);

                    if (discountAmountEl) {
                        discountAmountEl.value = formatId(gross);
                        discountAmountEl.focus();
                        discountAmountEl.select && discountAmountEl.select();
                    }

                    updatePreview();
                });
            }

            document.querySelectorAll('.item-free-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const formId = btn.getAttribute('data-form');
                    const unitPrice = Number(btn.getAttribute('data-unit-price') || 0);
                    const qty = Number(btn.getAttribute('data-qty') || 0);
                    const gross = Math.max(0, unitPrice * qty);

                    if (!formId) return;

                    const discountInput = document.querySelector('input[name="discount_amount"][form="' + formId + '"]');
                    if (!discountInput) return;

                    discountInput.value = formatId(gross);
                    discountInput.focus();
                    discountInput.select && discountInput.select();
                });
            });

            if (orthoCaseModeSelectInlineEl) {
                orthoCaseModeSelectInlineEl.addEventListener('change', function () {
                    if (!isSelectedTreatmentOrtho()) {
                        orthoCaseModeSelectInlineEl.value = 'none';
                    }
                    syncOrthoCaseModeVisibility();
                });
            }

            if (prostoCaseModeSelectInlineEl) {
                prostoCaseModeSelectInlineEl.addEventListener('change', function () {
                    if (!isSelectedTreatmentProsto()) {
                        prostoCaseModeSelectInlineEl.value = 'none';
                    }
                    syncProstoVisibility();
                });
            }

            if (paymentRowsWrapper) {
                paymentRowsWrapper.querySelectorAll('.payment-row-item').forEach(bindPaymentRow);
            }

            if (addPaymentRowBtn && paymentRowsWrapper && paymentRowTemplate) {
                addPaymentRowBtn.addEventListener('click', function () {
                    const fragment = paymentRowTemplate.content.cloneNode(true);
                    paymentRowsWrapper.appendChild(fragment);
                    const appendedRow = paymentRowsWrapper.lastElementChild;
                    bindPaymentRow(appendedRow);
                    updatePaymentPreview();
                    focusMethodSelect(appendedRow);
                });
            }

            syncTreatmentPriceUI();
            syncOrthoCaseModeVisibility();
            syncProstoVisibility();
            updatePreview();

            if (paymentRowsWrapper) {
                updatePaymentPreview();
            } else if (submitPayBtn) {
                submitPayBtn.disabled = false;
            }

            updatePaymentLock();
            handlePostAddItemFlow();
            handleBayarPas();
            handleAfterAddItem();
        })();
    </script>

</div>
@endsection