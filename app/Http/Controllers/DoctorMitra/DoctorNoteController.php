<?php

namespace App\Http\Controllers\DoctorMitra;

use App\Http\Controllers\Controller;
use App\Models\DoctorNote;
use App\Models\DoctorNoteNotification;
use App\Models\IncomeTransaction;
use App\Models\User;
use Illuminate\Http\Request;

class DoctorNoteController extends Controller
{
    public function showTransaction(Request $request, IncomeTransaction $incomeTransaction)
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        $this->authorizeTransactionAccess($user, $incomeTransaction);

        $incomeTransaction->load([
            'doctor',
            'patient',
            'items.treatment',
            'payments',
            'doctorNotes.doctor',
        ]);

        return view('doctor_mitra.transaction_show', [
            'incomeTransaction' => $incomeTransaction,
            'doctorNotes' => $incomeTransaction->doctorNotes,
            'isOwner' => $user->isOwner(),
            'isAdmin' => $user->isAdmin(),
            'isDokterMitra' => $user->isDokterMitra(),
        ]);
    }

    public function store(Request $request, IncomeTransaction $incomeTransaction)
    {
        $user = $request->user();

        if (!$user || !$user->isDokterMitra()) {
            abort(403, 'Hanya dokter mitra yang boleh membuat catatan.');
        }

        $this->authorizeTransactionAccess($user, $incomeTransaction);

        if (!$user->doctor_id) {
            return back()->with('error', 'Akun dokter mitra ini belum terhubung ke data dokter.');
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ], [
            'note.required' => 'Catatan wajib diisi.',
            'note.max' => 'Catatan maksimal 5000 karakter.',
        ]);

        $doctorNote = DoctorNote::create([
            'income_transaction_id' => $incomeTransaction->id,
            'doctor_id' => $user->doctor_id,
            'note' => trim((string) $validated['note']),
            'status' => DoctorNote::STATUS_ACTIVE,
        ]);

        // 🔥 UPDATED: notif owner + admin
        $this->createNotifications($doctorNote, $incomeTransaction);

        return redirect()
            ->route('doctor_mitra.transactions.show', $incomeTransaction)
            ->with('success', 'Catatan dokter berhasil ditambahkan.');
    }

    public function edit(Request $request, DoctorNote $doctorNote)
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        $doctorNote->load(['transaction.doctor', 'transaction.patient', 'doctor']);

        $this->authorizeNoteAccess($user, $doctorNote, true);

        return view('doctor_mitra.note_edit', [
            'doctorNote' => $doctorNote,
            'incomeTransaction' => $doctorNote->transaction,
            'isOwner' => $user->isOwner(),
            'isDokterMitra' => $user->isDokterMitra(),
        ]);
    }

    public function update(Request $request, DoctorNote $doctorNote)
    {
        $user = $request->user();

        if (!$user) {
            abort(403);
        }

        $this->authorizeNoteAccess($user, $doctorNote, true);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $doctorNote->update([
            'note' => trim((string) $validated['note']),
        ]);

        return redirect()
            ->route('doctor_mitra.transactions.show', $doctorNote->income_transaction_id)
            ->with('success', 'Catatan dokter berhasil diperbarui.');
    }

    public function markDone(Request $request, DoctorNote $doctorNote)
    {
        $user = $request->user();

        if (!$user || !$user->isOwner()) {
            abort(403, 'Hanya owner yang boleh menandai selesai.');
        }

        $doctorNote->update([
            'status' => DoctorNote::STATUS_DONE,
        ]);

        return redirect()
            ->route('doctor_mitra.transactions.show', $doctorNote->income_transaction_id)
            ->with('success', 'Catatan dokter ditandai selesai.');
    }

    public function archive(Request $request, DoctorNote $doctorNote)
    {
        $user = $request->user();

        if (!$user || !$user->isOwner()) {
            abort(403, 'Hanya owner yang boleh mengarsipkan catatan.');
        }

        $doctorNote->update([
            'status' => DoctorNote::STATUS_ARCHIVED,
        ]);

        return redirect()
            ->route('doctor_mitra.transactions.show', $doctorNote->income_transaction_id)
            ->with('success', 'Catatan dokter berhasil diarsipkan.');
    }

    public function destroy(Request $request, DoctorNote $doctorNote)
    {
        $user = $request->user();

        if (!$user || !$user->isOwner()) {
            abort(403, 'Hanya owner yang boleh menghapus catatan.');
        }

        $incomeTransactionId = $doctorNote->income_transaction_id;

        $doctorNote->delete();

        return redirect()
            ->route('doctor_mitra.transactions.show', $incomeTransactionId)
            ->with('success', 'Catatan dokter berhasil dihapus.');
    }

    // 🔥 UPDATED: owner + admin bisa buka notif
    public function openNotification(Request $request, DoctorNoteNotification $notification)
    {
        $user = $request->user();

        if (!$user || (!$user->isOwner() && !$user->isAdmin())) {
            abort(403, 'Hanya owner/admin yang boleh membuka notifikasi ini.');
        }

        $notification->update([
            'status' => DoctorNoteNotification::STATUS_READ,
            'owner_user_id' => $user->id,
        ]);

        return redirect()->route('doctor_mitra.transactions.show', $notification->income_transaction_id);
    }

    protected function authorizeTransactionAccess(User $user, IncomeTransaction $incomeTransaction): void
    {
        if ($user->isOwner() || $user->isAdmin()) {
            return;
        }

        if ($user->isDokterMitra()) {
            if (!$user->doctor_id) {
                abort(403);
            }

            if ((int) $incomeTransaction->doctor_id !== (int) $user->doctor_id) {
                abort(403);
            }

            return;
        }

        abort(403);
    }

    protected function authorizeNoteAccess(User $user, DoctorNote $doctorNote, bool $forEdit = false): void
    {
        if ($user->isOwner() || $user->isAdmin()) {
            return;
        }

        if ($user->isDokterMitra()) {
            if ((int) $doctorNote->doctor_id !== (int) $user->doctor_id) {
                abort(403);
            }

            if ($forEdit && (string) $doctorNote->status !== DoctorNote::STATUS_ACTIVE) {
                abort(403);
            }

            return;
        }

        abort(403);
    }

    // 🔥 UPDATED CORE
    protected function createNotifications(DoctorNote $doctorNote, IncomeTransaction $incomeTransaction): void
    {
        $users = User::query()
            ->whereIn('role', [User::ROLE_OWNER, User::ROLE_ADMIN])
            ->where('is_active', 1)
            ->get(['id']);

        foreach ($users as $user) {
            DoctorNoteNotification::create([
                'doctor_note_id' => $doctorNote->id,
                'income_transaction_id' => $incomeTransaction->id,
                'doctor_id' => $doctorNote->doctor_id,
                'owner_user_id' => $user->id,
                'status' => DoctorNoteNotification::STATUS_UNREAD,
            ]);
        }
    }
}