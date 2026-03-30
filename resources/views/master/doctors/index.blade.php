@extends('layouts.app')

@section('content')

<div class="container">

    <h2>Master Dokter</h2>

    <a href="{{ route('master.doctors.create') }}" class="btn btn-primary mb-3">
        + Tambah Dokter
    </a>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Nama</th>
                <th>Tipe</th>
                <th>Aktif</th>
                <th>Aksi</th>
            </tr>
        </thead>

        <tbody>
        @foreach($doctors as $doctor)
            <tr>
                <td>{{ $doctor->name }}</td>
                <td>{{ $doctor->type }}</td>
                <td>{{ $doctor->is_active ? 'Ya' : 'Tidak' }}</td>
                <td class="d-flex gap-1">
                    <a href="{{ route('master.doctors.edit', $doctor->id) }}" class="btn btn-sm btn-warning">
                        Edit
                    </a>

                    <form action="{{ route('master.doctors.destroy', $doctor->id) }}" method="POST"
                          onsubmit="return confirm('Yakin hapus dokter ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">
                            Hapus
                        </button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>

    </table>

</div>

@endsection