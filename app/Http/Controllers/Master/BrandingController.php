<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    public function edit()
    {
        $settings = Setting::first();

        if (!$settings) {
            $settings = Setting::create([
                'clinic_name' => 'Klinik Gigi',
            ]);
        }

        return view('master.branding.edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = Setting::first();

        if (!$settings) {
            $settings = Setting::create([
                'clinic_name' => 'Klinik Gigi',
            ]);
        }

        $validated = $request->validate([
            'clinic_name' => ['required','string','max:200'],
            'clinic_address' => ['nullable','string'],
            'clinic_phone' => ['nullable','string','max:50'],
            'owner_doctor_name' => ['nullable','string','max:150'],
        ]);

        $settings->update($validated);

        return back()->with('success','Branding klinik berhasil disimpan.');
    }
}