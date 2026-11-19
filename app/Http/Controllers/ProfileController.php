<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Riwayat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profil.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        // Jangan sampai aplikasi tidak punya admin sama sekali.
        if ($user->isAdmin() && ! \App\Models\User::where('role', 'admin')->where('status', 'approved')->where('id', '!=', $user->id)->exists()) {
            return back()->withErrors(
                ['password' => 'Anda satu-satunya admin aktif. Jadikan akun lain sebagai admin dulu sebelum menghapus akun ini.'],
                'userDeletion'
            );
        }

        Riwayat::catat(
            modul: 'auth',
            aksi: 'hapus',
            keterangan: "Akun {$user->name} (@{$user->username}) dihapus oleh pemiliknya",
            objekId: $user->id,
            objekLabel: "{$user->name} (@{$user->username})",
            lama: ['status' => $user->status, 'role' => $user->role],
        );

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
