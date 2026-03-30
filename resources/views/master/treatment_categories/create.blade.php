@extends('layouts.app')

@section('content')
<div class="container">

  <h2>Tambah Kategori Tindakan</h2>

  <form method="POST" action="{{ route('master.treatment_categories.store') }}">
    @csrf

    <div class="mb-3">
      <label class="form-label">Nama Kategori</label>
      <input type="text" name="name" class="form-control" required>
    </div>

    <div class="mb-3 form-check">
      <input type="checkbox" name="is_active" value="1" class="form-check-input" checked>
      <label class="form-check-label">Aktif</label>
    </div>

    <button class="btn btn-primary">Simpan</button>
    <a href="{{ route('master.treatment_categories.index') }}" class="btn btn-secondary">Kembali</a>

  </form>

</div>
@endsection