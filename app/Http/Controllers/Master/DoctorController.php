<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::orderBy('name')->get();
        return view('master.doctors.index', compact('doctors'));
    }

    public function create()
    {
        return view('master.doctors.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:owner,mitra,tamu'],
            'is_active' => ['nullable', 'in:0,1'],
        ]);

        $defaultFeePercent = 0;

        Doctor::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'default_fee_percent' => $defaultFeePercent,
            'is_active' => (int) ($validated['is_active'] ?? 0),
        ]);

        return redirect()
            ->route('master.doctors.index')
            ->with('success', 'Dokter berhasil ditambahkan.');
    }

    public function edit(Doctor $doctor)
    {
        return view('master.doctors.edit', compact('doctor'));
    }

    public function update(Request $request, Doctor $doctor)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:owner,mitra,tamu'],
            'is_active' => ['required', 'in:0,1'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Keamanan kompatibilitas
        |--------------------------------------------------------------------------
        | Kita tidak lagi menampilkan / mengedit fee di Master Dokter.
        | Namun agar tidak merusak rumus lama:
        | - owner/tamu tetap dipaksa 0
        | - mitra mempertahankan nilai lama yang sudah tersimpan
        */
        $defaultFeePercent = (float) ($doctor->default_fee_percent ?? 0);

        if (in_array($validated['type'], ['owner', 'tamu'], true)) {
            $defaultFeePercent = 0;
        }

        $doctor->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'default_fee_percent' => $defaultFeePercent,
            'is_active' => (int) $validated['is_active'],
        ]);

        return redirect()
            ->route('master.doctors.index')
            ->with('success', 'Dokter berhasil diupdate.');
    }

    public function destroy(Doctor $doctor)
    {
        $doctor->delete();

        return redirect()
            ->route('master.doctors.index')
            ->with('success', 'Dokter berhasil dihapus.');
    }
}