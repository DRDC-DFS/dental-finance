@extends('layouts.app')

@section('content')
<div class="card shadow-sm">
    <div class="card-body p-4">
        <h4 class="mb-1">{{ $title ?? 'Coming Soon' }}</h4>
        <div class="text-muted mb-3">{{ $subtitle ?? 'Halaman sedang disiapkan.' }}</div>

        <div class="alert alert-info mb-0">
            Halaman ini adalah placeholder agar NAVBAR aktif dan tidak 404.
            Nanti akan diganti modul real sesuai roadmap DFS.
        </div>
    </div>
</div>
@endsection