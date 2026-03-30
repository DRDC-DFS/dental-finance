<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function edit()
    {
        $setting = Setting::firstOrCreate(['id' => 1], ['clinic_name' => 'Klinik Gigi']);
        return view('master.settings.edit', compact('setting'));
    }

    public function update(Request $request)
    {
        $setting = Setting::firstOrCreate(['id' => 1], ['clinic_name' => 'Klinik Gigi']);

        $validated = $request->validate([
            'clinic_name' => ['nullable', 'string', 'max:200'],
            'owner_doctor_name' => ['nullable', 'string', 'max:150'],

            // upload
            'logo' => ['nullable', 'image', 'max:2048'],        // 2MB
            'background' => ['nullable', 'image', 'max:4096'],  // 4MB
        ]);

        if (array_key_exists('clinic_name', $validated) && $validated['clinic_name'] !== null) {
            $setting->clinic_name = $validated['clinic_name'];
        }

        if (array_key_exists('owner_doctor_name', $validated) && $validated['owner_doctor_name'] !== null) {
            $setting->owner_doctor_name = $validated['owner_doctor_name'];
        }

        // LOGO
        if ($request->hasFile('logo')) {
            if (!empty($setting->logo_path) && Storage::disk('public')->exists($setting->logo_path)) {
                Storage::disk('public')->delete($setting->logo_path);
            }

            $path = $request->file('logo')->store('branding', 'public');
            $setting->logo_path = $path;
        }

        // BACKGROUND (login/app background)
        if ($request->hasFile('background')) {
            if (!empty($setting->login_background_path) && Storage::disk('public')->exists($setting->login_background_path)) {
                Storage::disk('public')->delete($setting->login_background_path);
            }

            $path = $request->file('background')->store('branding', 'public');
            $setting->login_background_path = $path;
        }

        $setting->save();

        return redirect()->route('master.settings.edit')->with('success', 'Setting klinik berhasil disimpan.');
    }
}