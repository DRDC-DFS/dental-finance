@extends('layouts.app')

@section('content')
<div class="container py-3">
    <h4 class="mb-2">{{ $title ?? 'Inventory' }}</h4>
    <div class="text-muted mb-3">
        Mode sementara (placeholder). Type: <b>{{ $type ?? '-' }}</b>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="alert alert-warning">
        Modul ini belum diaktifkan ke database.
        Nanti kita sambungkan ke tabel <b>inventory_movements</b> dan laporan stok sesuai DATABASE SCHEMA FINAL.
    </div>

    <a href="{{ route('dashboard') }}" class="btn btn-secondary btn-sm">Kembali ke Dashboard</a>
</div>
@endsection