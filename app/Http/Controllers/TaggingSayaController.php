<?php

namespace App\Http\Controllers;

use App\Support\ProgresPetugas;
use Illuminate\Http\Request;

/**
 * Menu tunggal "Tagging" untuk viewer (mitra).
 *
 * Menggantikan alur lama yang tersebar di Dashboard, Penugasan Saya, Peta, dan Tagging:
 * satu halaman berisi kegiatan yang ditugaskan, SLS di dalamnya, progres, dan tombol
 * "Mulai / Lanjutkan tagging" yang langsung membuka peta pada SLS tersebut.
 *
 * Angka progres memakai ProgresPetugas (sumber yang sama dengan dashboard lama),
 * jadi tidak ada logika hitung baru.
 */
class TaggingSayaController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Admin tidak punya penugasan; arahkan ke ringkasan penugasan mitra.
        if ($user->isAdmin()) {
            return redirect()->route('admin.progres.index');
        }

        return view('tagging.index', ProgresPetugas::untuk($user));
    }
}
