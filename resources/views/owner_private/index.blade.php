@extends('layouts.app')

@section('content')
@php
    $today = now()->toDateString();
    $periodStart = $dateStart ?? request('date_start') ?? $today;
    $periodEnd = $dateEnd ?? request('date_end') ?? $today;
    $selectedType = $type ?? request('type');
    $selectedCategory = $category ?? request('category');
    $currentRoute = request()->route()?->getName();

    $formatTanggal = function ($value) {
        if (!$value) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d-m-Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };

    $categoryLabels = $categoryOptions ?? [];

    $formatCategory = function ($value) use ($categoryLabels) {
        if (!$value) {
            return '-';
        }

        return $categoryLabels[$value] ?? ucwords(str_replace('_', ' ', (string) $value));
    };

    $isSingleDayPeriod = $periodStart === $periodEnd;
    $isTodayMode = ($periodStart === $today && $periodEnd === $today);

    $periodeLabel = $isSingleDayPeriod
        ? 'Periode: ' . $formatTanggal($periodStart)
        : 'Periode: ' . $formatTanggal($periodStart) . ' s/d ' . $formatTanggal($periodEnd);
@endphp

<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0">Transaksi Private Owner</h4>
            <div class="text-muted small">Pemasukan dan pengeluaran khusus owner</div>
            <div class="text-danger small fw-bold mt-1">Data ini tidak masuk ke operasional klinik</div>
            <div class="text-success small fw-bold mt-1">Default tampilan: data hari berjalan</div>
            <div class="text-muted small">{{ $periodeLabel }}</div>
        </div>

        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('owner_private.create') }}" class="btn btn-primary btn-sm">
                + Tambah Transaksi Private
            </a>

            <a href="{{ route('owner_finance.index') }}"
               class="btn btn-outline-primary btn-sm {{ str_starts_with($currentRoute ?? '', 'owner_finance.') ? 'dfs-btn-active' : '' }}">
                Owner Finance
            </a>

            <a href="{{ route('reports.daily_cash.index') }}"
               class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.daily_cash.index' ? 'dfs-btn-active' : '' }}">
                Kas Harian
            </a>

            <a href="{{ route('reports.laba-rugi') }}"
               class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.laba-rugi' ? 'dfs-btn-active' : '' }}">
                Laba Rugi
            </a>

            <a href="{{ route('reports.fee_dokter.index') }}"
               class="btn btn-outline-secondary btn-sm {{ $currentRoute === 'reports.fee_dokter.index' ? 'dfs-btn-active' : '' }}">
                Fee Dokter
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success py-2">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger py-2">
            <div class="fw-bold mb-1">Terjadi kesalahan:</div>
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('owner_private.index') }}">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Mulai</label>
                        <input
                            type="date"
                            name="date_start"
                            value="{{ $periodStart }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Tanggal Selesai</label>
                        <input
                            type="date"
                            name="date_end"
                            value="{{ $periodEnd }}"
                            class="form-control"
                        >
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Tipe</label>
                        <select name="type" class="form-select">
                            <option value="">Semua</option>
                            <option value="income" {{ $selectedType === 'income' ? 'selected' : '' }}>Masuk</option>
                            <option value="expense" {{ $selectedType === 'expense' ? 'selected' : '' }}>Keluar</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Kategori</label>
                        <select name="category" class="form-select">
                            <option value="">Semua</option>
                            @foreach(($categoryOptions ?? []) as $value => $label)
                                <option value="{{ $value }}" {{ $selectedCategory === $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary btn-sm">
                                Filter
                            </button>

                            <a href="{{ route('owner_private.index', ['date_start' => $today, 'date_end' => $today]) }}"
                               class="btn btn-success btn-sm {{ $isTodayMode ? 'dfs-btn-active' : '' }}">
                                Hari Ini
                            </a>

                            <a href="{{ route('owner_private.index', ['date_start' => $today, 'date_end' => $today]) }}"
                               class="btn btn-secondary btn-sm {{ $isTodayMode ? 'dfs-btn-active' : '' }}">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Kategori</th>
                            <th>Sumber</th>
                            <th>Keterangan</th>
                            <th>Metode</th>
                            <th class="text-end">Masuk</th>
                            <th class="text-end">Keluar</th>
                            <th>Catatan</th>
                            <th class="text-start">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $sumIncome = 0;
                            $sumExpense = 0;
                            $rowNumber = method_exists($rows, 'firstItem') && $rows->firstItem() ? $rows->firstItem() : 1;
                        @endphp

                        @forelse($rows as $i => $r)
                            @php
                                $isIncome = ($r->type ?? '') === 'income';
                                $masuk = $isIncome ? (float) $r->amount : 0;
                                $keluar = !$isIncome ? (float) $r->amount : 0;

                                $sumIncome += $masuk;
                                $sumExpense += $keluar;
                            @endphp

                            <tr>
                                <td>{{ $rowNumber + $i }}</td>
                                <td>{{ \Carbon\Carbon::parse($r->trx_date)->format('d-m-Y') }}</td>
                                <td>
                                    @if($isIncome)
                                        <span class="badge text-bg-success">Masuk</span>
                                    @else
                                        <span class="badge text-bg-danger">Keluar</span>
                                    @endif
                                </td>
                                <td>{{ $formatCategory($r->category) }}</td>
                                <td>{{ $r->source ?: '-' }}</td>
                                <td>{{ $r->description }}</td>
                                <td>{{ $r->payment_method }}</td>
                                <td class="text-end fw-semibold">{{ $masuk > 0 ? rupiah($masuk) : '-' }}</td>
                                <td class="text-end fw-semibold">{{ $keluar > 0 ? rupiah($keluar) : '-' }}</td>
                                <td>{{ $r->notes ?: '-' }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a href="{{ route('owner_private.edit', $r->id) }}" class="btn btn-outline-primary btn-sm">
                                            Edit
                                        </a>

                                        <form method="POST"
                                              action="{{ route('owner_private.destroy', $r->id) }}"
                                              onsubmit="return confirm('Hapus transaksi private owner ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                Hapus
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11">Belum ada transaksi private owner pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="7">JUMLAH</td>
                            <td class="text-end">{{ rupiah($sumIncome) }}</td>
                            <td class="text-end">{{ rupiah($sumExpense) }}</td>
                            <td colspan="2"></td>
                        </tr>
                        <tr class="fw-bold">
                            <td colspan="7">NET PRIVATE OWNER</td>
                            <td colspan="2" class="text-end">{{ rupiah($sumIncome - $sumExpense) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="mt-3">
                {{ $rows->links() }}
            </div>
        </div>
    </div>

</div>
@endsection