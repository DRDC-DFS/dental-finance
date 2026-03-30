@extends('layouts.app')

@section('content')
<div class="container">

  <h2>Kategori Tindakan</h2>

  <a href="{{ route('master.treatment_categories.create') }}" class="btn btn-primary mb-3">
    + Tambah Kategori
  </a>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Nama</th>
        <th>Aktif</th>
        <th style="width:160px;">Aksi</th>
      </tr>
    </thead>
    <tbody>
      @foreach($categories as $c)
        <tr>
          <td>{{ $c->name }}</td>
          <td>{{ $c->is_active ? 'Ya' : 'Tidak' }}</td>
          <td class="d-flex gap-1">
            <a href="{{ route('master.treatment_categories.edit', $c->id) }}" class="btn btn-sm btn-warning">
              Edit
            </a>

            <form method="POST"
                  action="{{ route('master.treatment_categories.destroy', $c->id) }}"
                  onsubmit="return confirm('Yakin hapus kategori ini?')">
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