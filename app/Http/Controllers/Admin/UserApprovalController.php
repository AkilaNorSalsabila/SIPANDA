<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Riwayat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

/**
 * Manajemen akun (khusus admin): persetujuan pendaftaran, tambah akun,
 * ubah peran (viewer / admin), nonaktifkan. Penugasan SLS ada di PenugasanController.
 */
class UserApprovalController extends Controller
{
    private const PENDING  = 'pending';
    private const APPROVED = 'approved';
    private const REJECTED = 'rejected';
    private const INACTIVE = 'nonaktif';

    public function index(Request $request)
    {
        $statuses = [self::PENDING, self::APPROVED, self::REJECTED, self::INACTIVE];

        $status = in_array($request->query('status'), $statuses, true)
            ? $request->query('status')
            : self::PENDING;

        $role = array_key_exists((string) $request->query('role'), User::ROLE) ? $request->query('role') : null;

        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->withCount('penugasan')
            ->where('status', $status)
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                      ->orWhere('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $counts = User::query()
            ->when($role, fn ($q) => $q->where('role', $role))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.users.index', [
            'users'  => $users,
            'status' => $status,
            'role'   => $role,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    /** Tambah akun langsung oleh admin (langsung aktif), dengan pilihan peran. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', 'lowercase', 'unique:users,username'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone'    => ['nullable', 'string', 'max:20'],
            'role'     => ['required', Rule::in(array_keys(User::ROLE))],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [], ['role' => 'peran']);

        $user = User::create([
            'name'     => $data['name'],
            'username' => strtolower($data['username']),
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'role'     => $data['role'],
            'status'   => self::APPROVED,
        ]);

        $label = $this->label($user);
        Riwayat::catat(
            modul: 'akun',
            aksi: 'tambah',
            keterangan: "Menambah akun {$label} dengan peran " . $user->labelRole(),
            objekId: $user->id,
            objekLabel: $label,
            baru: $user->only(['status', 'role']),
        );

        return redirect()
            ->route('admin.users.index', ['status' => self::APPROVED])
            ->with('success', "Akun {$user->name} ({$user->labelRole()}) berhasil dibuat.");
    }

    /**
     * Setujui akun (dari menunggu / ditolak / nonaktif).
     * Peran: yang dipilih admin; kalau tidak dipilih, peran yang sudah ada (default viewer).
     */
    public function approve(Request $request, User $user)
    {
        $request->validate(['role' => ['nullable', Rule::in(array_keys(User::ROLE))]]);

        $lama = $user->only(['status', 'role']);

        $user->role   = $request->input('role') ?: ($user->role ?: 'viewer');
        $user->status = self::APPROVED;
        $user->save();

        $this->catatRiwayat($user, 'setujui', 'Menyetujui', $lama);

        return back()->with('success', "Akun {$user->name} berhasil disetujui sebagai {$user->labelRole()}.");
    }

    public function reject(User $user)
    {
        $this->guardAkunSendiri($user);
        $this->guardAdminTerakhir($user);

        $lama = $user->only(['status', 'role']);

        $user->status = self::REJECTED;
        $user->save();

        $this->catatRiwayat($user, 'tolak', 'Menolak pendaftaran', $lama);

        return back()->with('success', "Pendaftaran {$user->name} ditolak.");
    }

    public function deactivate(User $user)
    {
        $this->guardAkunSendiri($user);
        $this->guardAdminTerakhir($user);

        $lama = $user->only(['status', 'role']);

        $user->status = self::INACTIVE;
        $user->save();

        $this->catatRiwayat($user, 'nonaktifkan', 'Menonaktifkan', $lama);

        return back()->with('success', "Akun {$user->name} dinonaktifkan.");
    }

    /** Ubah peran akun yang sudah ada (viewer <-> admin). */
    public function updateRole(Request $request, User $user)
    {
        $data = $request->validate(['role' => ['required', Rule::in(array_keys(User::ROLE))]]);

        if ($user->role === $data['role']) {
            return back()->with('success', "Peran {$user->name} tidak berubah.");
        }

        $this->guardAkunSendiri($user, 'Anda tidak dapat mengubah peran akun Anda sendiri.');
        $this->guardAdminTerakhir($user);

        $lama = $user->only(['status', 'role']);

        $user->role = $data['role'];
        $user->save();

        $this->catatRiwayat($user, 'ubah', 'Mengubah peran', $lama);

        return back()->with('success', "Peran {$user->name} sekarang {$user->labelRole()}.");
    }

    /** RIWAYAT: catat perubahan status/peran akun (nilai lama diambil sebelum diubah). */
    private function catatRiwayat(User $user, string $aksi, string $uraian, array $lama): void
    {
        $label = $this->label($user);

        Riwayat::catat(
            modul: 'akun',
            aksi: $aksi,
            keterangan: "{$uraian} akun {$label}",
            objekId: $user->id,
            objekLabel: $label,
            lama: $lama,
            baru: $user->only(['status', 'role']),
        );
    }

    private function label(User $user): string
    {
        return "{$user->name} (@{$user->username})";
    }

    /** Admin tidak boleh menolak / menonaktifkan / menurunkan akunnya sendiri (bisa terkunci keluar). */
    private function guardAkunSendiri(User $user, string $pesan = 'Anda tidak dapat menonaktifkan akun Anda sendiri.'): void
    {
        abort_if($user->id === auth()->id(), 403, $pesan);
    }

    /** Selalu sisakan minimal satu admin aktif. */
    private function guardAdminTerakhir(User $user): void
    {
        if ($user->role !== 'admin' || $user->status !== self::APPROVED) {
            return;
        }

        $adaLain = User::where('role', 'admin')
            ->where('status', self::APPROVED)
            ->where('id', '!=', $user->id)
            ->exists();

        abort_unless($adaLain, 403, 'Harus tersisa minimal satu admin aktif.');
    }
}
