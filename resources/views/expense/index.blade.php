@extends('layouts.app')

@section('content')
@php
    $role = strtolower((string) (auth()->user()->role ?? ''));
    $isOwner = $role === 'owner';
    $today = now()->toDateString();

    if ($isOwner) {
        $periodStart = $dateStart ?? request('date_start') ?? $today;
        $periodEnd = $dateEnd ?? request('date_end') ?? $today;
    } else {
        $singleDate = $date ?? request('date') ?? $today;
        $periodStart = $singleDate;
        $periodEnd = $singleDate;
    }

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

    $isSingleDayPeriod = $periodStart === $periodEnd;
    $periodeLabel = $isSingleDayPeriod
        ? 'Periode: ' . $formatTanggal($periodStart)
        : 'Periode: ' . $formatTanggal($periodStart) . ' s/d ' . $formatTanggal($periodEnd);
@endphp

<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div>
            <h4 class="mb-0">Pengeluaran</h4>
            <div class="text-muted small">Daftar transaksi pengeluaran</div>
            <div class="text-success small fw-bold mt-1">Default tampilan: data hari berjalan</div>
            <div class="text-muted small">{{ $periodeLabel }}</div>
        </div>

        <a href="{{ route('expenses.create') }}" class="btn btn-primary btn-sm">
            + Tambah Pengeluaran
        </a>
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
            <form method="GET" action="{{ route('expenses.index') }}">
                <div class="row g-3 align-items-end">
                    @if($isOwner)
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

                        <div class="col-md-6">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Filter
                                </button>

                                <a href="{{ route('expenses.index', ['date_start' => $today, 'date_end' => $today]) }}"
                                   class="btn btn-success btn-sm">
                                    Hari Ini
                                </a>

                                <a href="{{ route('expenses.index', ['date_start' => $today, 'date_end' => $today]) }}"
                                   class="btn btn-secondary btn-sm">
                                    Reset
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Tanggal</label>
                            <input
                                type="date"
                                name="date"
                                value="{{ $periodStart }}"
                                class="form-control"
                            >
                        </div>

                        <div class="col-md-6">
                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Filter
                                </button>

                                <a href="{{ route('expenses.index', ['date' => $today]) }}"
                                   class="btn btn-success btn-sm">
                                    Hari Ini
                                </a>

                                <a href="{{ route('expenses.index', ['date' => $today]) }}"
                                   class="btn btn-secondary btn-sm">
                                    Reset
                                </a>
                            </div>
                        </div>
                    @endif
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
                            <th class="text-start">No</th>
                            <th class="text-start">Tanggal</th>
                            <th class="text-start">Nama Pengeluaran</th>
                            <th class="text-end">Tunai</th>
                            <th class="text-end">BCA</th>
                            <th class="text-end">BNI</th>
                            <th class="text-end">BRI</th>
                            <th class="text-end">Total</th>
                            <th class="text-start">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $sumTunai = 0; $sumBca = 0; $sumBni = 0; $sumBri = 0; $sumTotal = 0;
                            $me = auth()->user();
                            $tableIsOwner = $me && strtolower((string)$me->role) === 'owner';
                            $myId = $me?->id;
                            $rowNumber = method_exists($rows, 'firstItem') && $rows->firstItem() ? $rows->firstItem() : 1;
                        @endphp

                        @forelse($rows as $i => $r)
                            @php
                                $tunai = $r->pay_method === 'TUNAI' ? (float)$r->amount : 0;
                                $bca   = $r->pay_method === 'BCA'   ? (float)$r->amount : 0;
                                $bni   = $r->pay_method === 'BNI'   ? (float)$r->amount : 0;
                                $bri   = $r->pay_method === 'BRI'   ? (float)$r->amount : 0;
                                $total = (float)$r->amount;

                                $sumTunai += $tunai;
                                $sumBca += $bca;
                                $sumBni += $bni;
                                $sumBri += $bri;
                                $sumTotal += $total;

                                $canEdit = false;
                                $canDelete = false;

                                if ($tableIsOwner) {
                                    $canEdit = true;
                                    $canDelete = true;
                                } else {
                                    $canEdit = ($myId && (int)$r->created_by === (int)$myId && (bool)$r->is_private === false);
                                    $canDelete = false;
                                }
                            @endphp

                            <tr>
                                <td>{{ $rowNumber + $i }}</td>
                                <td>{{ tgl_id($r->expense_date, 'd M Y') }}</td>
                                <td>{{ $r->name }}</td>

                                <td class="text-end">{{ rupiah($tunai) }}</td>
                                <td class="text-end">{{ rupiah($bca) }}</td>
                                <td class="text-end">{{ rupiah($bni) }}</td>
                                <td class="text-end">{{ rupiah($bri) }}</td>

                                <td class="text-end fw-semibold">{{ rupiah($total) }}</td>

                                <td>
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        @if($canEdit)
                                            <a class="btn btn-outline-primary btn-sm"
                                               href="{{ route('expenses.edit', $r->id) }}">
                                                Edit
                                            </a>

                                            <a class="btn btn-outline-secondary btn-sm"
                                               href="{{ route('expenses.edit', $r->id) }}">
                                                Sumber Data
                                            </a>
                                        @elseif($tableIsOwner)
                                            <a class="btn btn-outline-secondary btn-sm"
                                               href="{{ route('expenses.edit', $r->id) }}">
                                                Sumber Data
                                            </a>
                                        @else
                                            <span>-</span>
                                        @endif

                                        @if($canDelete)
                                            <form method="POST"
                                                  action="{{ route('expenses.destroy', $r->id) }}"
                                                  onsubmit="return confirm('Hapus pengeluaran ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" type="submit">
                                                    Hapus
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">Belum ada data pengeluaran untuk filter yang dipilih.</td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot class="table-light">
                        <tr class="fw-bold">
                            <td colspan="3">JUMLAH</td>
                            <td class="text-end">{{ rupiah($sumTunai) }}</td>
                            <td class="text-end">{{ rupiah($sumBca) }}</td>
                            <td class="text-end">{{ rupiah($sumBni) }}</td>
                            <td class="text-end">{{ rupiah($sumBri) }}</td>
                            <td class="text-end">{{ rupiah($sumTotal) }}</td>
                            <td></td>
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