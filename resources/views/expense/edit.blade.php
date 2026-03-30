@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0">Edit Pengeluaran</h4>
            <div class="text-muted small">Perbarui transaksi pengeluaran</div>
        </div>
        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">Kembali</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <div class="fw-semibold mb-1">Periksa kembali input:</div>
            <ul class="mb-0">
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $me = auth()->user();
        $isOwner = $me && strtolower((string)$me->role) === 'owner';
        $amountValue = old('amount', $expense->amount);
    @endphp

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.update', $expense->id) }}" id="expenseForm">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="expense_date" class="form-control"
                               value="{{ old('expense_date', $expense->expense_date) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Via</label>
                        <select name="pay_method" class="form-select" required>
                            @php $pm = old('pay_method', $expense->pay_method); @endphp
                            <option value="TUNAI" @selected($pm==='TUNAI')>TUNAI</option>
                            <option value="BCA" @selected($pm==='BCA')>BCA</option>
                            <option value="BNI" @selected($pm==='BNI')>BNI</option>
                            <option value="BRI" @selected($pm==='BRI')>BRI</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Jumlah (Rp)</label>

                        <input type="text" id="amount_display" class="form-control text-end"
                               inputmode="numeric" autocomplete="off" placeholder="Rp 0">

                        <input type="hidden" id="amount" name="amount" value="{{ $amountValue }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label">Nama/Jenis Pengeluaran</label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $expense->name) }}" required maxlength="255">
                    </div>

                    @if($isOwner)
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_private" value="1" id="is_private"
                                       @checked(old('is_private', (bool)$expense->is_private))>
                                <label class="form-check-label" for="is_private">
                                    Privat (hanya Owner)
                                </label>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary">Batal</a>
                </div>

            </form>
        </div>
    </div>

</div>

<script>
(function () {
    const display = document.getElementById('amount_display');
    const hidden = document.getElementById('amount');
    const form = document.getElementById('expenseForm');

    function onlyDigits(str) {
        return (str || '').toString().replace(/[^\d]/g, '');
    }

    function formatRupiahNoDecimal(numStr) {
        numStr = onlyDigits(numStr);
        if (numStr === '') return 'Rp 0';
        numStr = numStr.replace(/^0+(?=\d)/, '');
        const withDots = numStr.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return 'Rp ' + withDots;
    }

    function syncFromHidden() {
        display.value = formatRupiahNoDecimal(hidden.value);
    }

    function syncFromDisplay() {
        const raw = onlyDigits(display.value);
        hidden.value = raw === '' ? '0' : raw;
        display.value = formatRupiahNoDecimal(raw);
    }

    syncFromHidden();
    display.addEventListener('input', syncFromDisplay);
    display.addEventListener('blur', syncFromDisplay);

    form.addEventListener('submit', function () {
        syncFromDisplay();
    });
})();
</script>
@endsection