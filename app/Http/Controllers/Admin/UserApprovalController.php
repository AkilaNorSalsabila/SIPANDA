<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

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

        $search = trim((string) $request->query('q'));

        $users = $this->nonAdmin()
            ->where('status', $status)
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

        $counts = $this->nonAdmin()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.users.index', [
            'users'  => $users,
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    /** Setujui akun (dari menunggu / ditolak / nonaktif). Peran selalu viewer. */
    public function approve(User $user)
    {
        $this->guardNotAdmin($user);

        $user->role   = 'viewer'; // semua akun non-admin hanya melihat peta
        $user->status = self::APPROVED;
        $user->save();

        return back()->with('success', "Akun {$user->name} berhasil disetujui.");
    }

    public function reject(User $user)
    {
        $this->guardNotAdmin($user);

        $user->status = self::REJECTED;
        $user->save();

        return back()->with('success', "Pendaftaran {$user->name} ditolak.");
    }

    public function deactivate(User $user)
    {
        $this->guardNotAdmin($user);

        $user->status = self::INACTIVE;
        $user->save();

        return back()->with('success', "Akun {$user->name} dinonaktifkan.");
    }

    /** Semua akun selain admin. */
    private function nonAdmin()
    {
        return User::query()->where(function ($q) {
            $q->whereNull('role')->orWhere('role', '!=', 'admin');
        });
    }

    private function guardNotAdmin(User $user): void
    {
        abort_if($user->isAdmin(), 403, 'Akun admin tidak dapat diubah dari halaman ini.');
    }
}
