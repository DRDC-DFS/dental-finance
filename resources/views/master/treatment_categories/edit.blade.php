@extends('layouts.app')

@section('content')
<div class="container">

    <h2>Edit Kategori Tindakan</h2>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('master.treatment_categories.update', $category->id) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label class="form-label">Nama Kategori</label>
            <input type="text"
                   name="name"
                   class="form-control"
                   required
                   value="{{ old('name', $category->name) }}">
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox"
                   name="is_active"
                   value="1"
                   class="form-check-input"
                   id="is_active"
                   {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Aktif</label>
        </div>

        <button class="btn btn-primary">Update</button>
        <a href="{{ route('master.treatment_categories.index') }}" class="btn btn-secondary">Kembali</a>
    </form>

</div>
@endsection