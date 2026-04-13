<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
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

    private function availableRoles(): array
    {
        return [
            User::ROLE_ADMIN => 'Admin',
            User::ROLE_DOKTER_MITRA => 'Dokter Mitra',
        ];
    }

    /**
     * List user (owner + admin + dokter mitra terlihat)
     */
    public function index(Request $request)
    {
        $this->ensureOwnerOnly();

        $q = trim((string) $request->get('q', ''));

        $users = User::query()
            ->with('doctor')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhereHas('doctor', function ($doctorQuery) use ($q) {
                            $doctorQuery->where('name', 'like', "%{$q}%");
                        });
                });
            })
            ->orderByRaw("FIELD(role, 'owner','admin','dokter_mitra','staff')")
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('master.users.index', compact('users', 'q'));
    }

    public function create()
    {
        $this->ensureOwnerOnly();

        $roles = $this->availableRoles();

        $doctors = Doctor::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return view('master.users.create', compact('roles', 'doctors'));
    }

    /**
     * Simpan user baru (admin / dokter mitra)
     */
    public function store(Request $request)
    {
        $this->ensureOwnerOnly();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys($this->availableRoles()))],
            'doctor_id' => [
                'nullable',
                'integer',
                Rule::exists('doctors', 'id'),
                function ($attribute, $value, $fail) use ($request) {
                    $role = (string) $request->input('role');

                    if ($role === User::ROLE_DOKTER_MITRA && empty($value)) {
                        $fail('Dokter harus dipilih untuk akun dokter mitra.');
                    }

                    if ($role === User::ROLE_DOKTER_MITRA && !empty($value)) {
                        $exists = User::query()
                            ->where('role', User::ROLE_DOKTER_MITRA)
                            ->where('doctor_id', $value)
                            ->exists();

                        if ($exists) {
                            $fail('Dokter ini sudah terhubung ke akun dokter mitra lain.');
                        }
                    }
                },
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'role.required' => 'Role wajib dipilih.',
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat jpg, jpeg, png, atau webp.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('users/photos', 'public');
        }

        $role = strtolower((string) $validated['role']);

        User::create([
            'name' => $validated['name'],
            'email' => strtolower((string) $validated['email']),
            'password' => Hash::make((string) $validated['password']),
            'photo_path' => $photoPath,
            'role' => $role,
            'doctor_id' => $role === User::ROLE_DOKTER_MITRA
                ? (int) ($validated['doctor_id'] ?? 0) ?: null
                : null,
            'is_active' => (int) ($validated['is_active'] ?? 1),
        ]);

        $successLabel = $role === User::ROLE_DOKTER_MITRA ? 'Akun dokter mitra berhasil dibuat.' : 'Admin berhasil dibuat.';

        return redirect()->route('master.users.index')->with('success', $successLabel);
    }

    public function edit(User $user)
    {
        $this->ensureOwnerOnly();
        abort_unless(in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_DOKTER_MITRA], true), 404);

        $roles = $this->availableRoles();

        $doctors = Doctor::query()
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return view('master.users.edit', compact('user', 'roles', 'doctors'));
    }

    /**
     * Update user (admin / dokter mitra)
     */
    public function update(Request $request, User $user)
    {
        $this->ensureOwnerOnly();
        abort_unless(in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_DOKTER_MITRA], true), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:191', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(array_keys($this->availableRoles()))],
            'doctor_id' => [
                'nullable',
                'integer',
                Rule::exists('doctors', 'id'),
                function ($attribute, $value, $fail) use ($request, $user) {
                    $role = (string) $request->input('role');

                    if ($role === User::ROLE_DOKTER_MITRA && empty($value)) {
                        $fail('Dokter harus dipilih untuk akun dokter mitra.');
                    }

                    if ($role === User::ROLE_DOKTER_MITRA && !empty($value)) {
                        $exists = User::query()
                            ->where('role', User::ROLE_DOKTER_MITRA)
                            ->where('doctor_id', $value)
                            ->where('id', '!=', $user->id)
                            ->exists();

                        if ($exists) {
                            $fail('Dokter ini sudah terhubung ke akun dokter mitra lain.');
                        }
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'role.required' => 'Role wajib dipilih.',
            'photo.image' => 'File foto harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat jpg, jpeg, png, atau webp.',
            'photo.max' => 'Ukuran foto maksimal 2MB.',
        ]);

        $role = strtolower((string) $validated['role']);

        $user->name = $validated['name'];
        $user->email = strtolower((string) $validated['email']);
        $user->role = $role;
        $user->doctor_id = $role === User::ROLE_DOKTER_MITRA
            ? (int) ($validated['doctor_id'] ?? 0) ?: null
            : null;
        $user->is_active = (int) ($validated['is_active'] ?? 1);

        if (!empty($validated['password'])) {
            $user->password = Hash::make((string) $validated['password']);
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

        $successLabel = $role === User::ROLE_DOKTER_MITRA ? 'Akun dokter mitra berhasil diupdate.' : 'Admin berhasil diupdate.';

        return redirect()->route('master.users.index')->with('success', $successLabel);
    }

    /**
     * Aktif/Nonaktif user non-owner
     */
    public function toggle(User $user)
    {
        $this->ensureOwnerOnly();
        abort_unless(in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_DOKTER_MITRA], true), 404);

        $user->is_active = $user->is_active ? 0 : 1;
        $user->save();

        $label = $user->role === User::ROLE_DOKTER_MITRA ? 'Status dokter mitra berhasil diubah.' : 'Status admin berhasil diubah.';

        return redirect()->route('master.users.index')->with('success', $label);
    }

    /**
     * Reset password user non-owner ke password default sementara
     */
    public function resetPassword(User $user)
    {
        $this->ensureOwnerOnly();
        abort_unless(in_array((string) $user->role, [User::ROLE_ADMIN, User::ROLE_DOKTER_MITRA], true), 404);

        $defaultPassword = '12345678';

        $user->password = Hash::make($defaultPassword);
        $user->save();

        $label = $user->role === User::ROLE_DOKTER_MITRA ? 'Password dokter mitra berhasil direset menjadi: ' : 'Password admin berhasil direset menjadi: ';

        return redirect()->route('master.users.index')
            ->with('success', $label . $defaultPassword);
    }
}
