<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Riwayat;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'lowercase', 'unique:users,username'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role_diminta' => ['nullable', Rule::in(array_keys(User::ROLE))],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name'     => $request->name,
            'username' => strtolower($request->username),
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            // Peran SELALU viewer sampai admin menyetujui. Pilihan "Admin" di form
            // hanya disimpan sebagai permintaan (role_diminta) untuk ditinjau admin.
            'role'     => 'viewer',
            'role_diminta' => $request->input('role_diminta') ?: 'viewer',
            'status'   => 'pending',  // wajib disetujui admin dulu sebelum bisa login
        ]);

        event(new Registered($user));

        Riwayat::catat(
            modul: 'auth',
            aksi: 'daftar',
            keterangan: "Pendaftaran akun baru: {$user->name} (@{$user->username}) meminta peran " . (User::ROLE[$user->role_diminta] ?? 'Viewer') . ', menunggu persetujuan admin',
            objekId: $user->id,
            objekLabel: "{$user->name} (@{$user->username})",
            pelaku: $user,
        );

        // Sengaja TIDAK auto-login karena status masih "pending".
        return redirect()->route('login')->with(
            'status',
            'Pendaftaran berhasil. Akun Anda menunggu persetujuan admin sebelum dapat digunakan.'
        );
    }
}
