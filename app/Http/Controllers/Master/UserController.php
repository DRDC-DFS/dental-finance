<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function ensureOwnerOnly(): void
    {
        $user = auth()->user();
        $role = strtolower((string) ($user->role ?? ''));

        if (!$user || $role !== 'owner') {
            abort(403, 'Hanya OWNER yang boleh mengakses Master User.');
        }
    }

    /**
     * List user (admin + owner terlihat, tapi aksi hanya untuk admin)
     */
    public function index(Request $request)
    {
        $this->ensureOwnerOnly();

        $q = trim((string) $request->get('q', ''));

        $users = User::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                       ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByRaw("FIELD(role, 'owner','admin','staff')")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('master.users.index', compact('users', 'q'));
    }

    public function create()
    {
        $this->ensureOwnerOnly();

        return view('master.users.create');
    }

    /**
     * Simpan admin baru
     */
    public function store(Request $request)
    {
        $this->ensureOwnerOnly();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat jpg, jpeg, png, atau webp.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('users/photos', 'public');
        }

        User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
            'photo_path' => $photoPath,
            'role' => 'admin',
            'is_active' => (int) ($validated['is_active'] ?? 1),
        ]);

        return redirect()->route('master.users.index')->with('success', 'Admin berhasil dibuat.');
    }

    public function edit(User $user)
    {
        $this->ensureOwnerOnly();
        abort_unless($user->role === 'admin', 404);

        return view('master.users.edit', compact('user'));
    }

    /**
     * Update admin
     */
    public function update(Request $request, User $user)
    {
        $this->ensureOwnerOnly();
        abort_unless($user->role === 'admin', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat jpg, jpeg, png, atau webp.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $user->name = $validated['name'];
        $user->email = strtolower($validated['email']);
        $user->is_active = (int) ($validated['is_active'] ?? 1);

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $removePhoto = (bool) ($validated['remove_photo'] ?? false);

        if ($removePhoto && !empty($user->photo_path) && Storage::disk('public')->exists($user->photo_path)) {
            Storage::disk('public')->delete($user->photo_path);
            $user->photo_path = null;
        }

        if ($request->hasFile('photo')) {
            if (!empty($user->photo_path) && Storage::disk('public')->exists($user->photo_path)) {
                Storage::disk('public')->delete($user->photo_path);
            }

            $user->photo_path = $request->file('photo')->store('users/photos', 'public');
        }

        $user->save();

        return redirect()->route('master.users.index')->with('success', 'Admin berhasil diupdate.');
    }

    /**
     * Aktif/Nonaktif admin
     */
    public function toggle(User $user)
    {
        $this->ensureOwnerOnly();
        abort_unless($user->role === 'admin', 404);

        $user->is_active = $user->is_active ? 0 : 1;
        $user->save();

        return redirect()->route('master.users.index')->with('success', 'Status admin berhasil diubah.');
    }

    /**
     * Reset password admin ke password default sementara
     */
    public function resetPassword(User $user)
    {
        $this->ensureOwnerOnly();
        abort_unless($user->role === 'admin', 404);

        $defaultPassword = '12345678';

        $user->password = Hash::make($defaultPassword);
        $user->save();

        return redirect()->route('master.users.index')
            ->with('success', 'Password admin berhasil direset menjadi: ' . $defaultPassword);
    }
}