<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->status !== 'approved') {
            Auth::logout();

            return redirect()->route('login')->withErrors([
                'login' => 'Sesi Anda berakhir karena status akun tidak lagi aktif.',
            ]);
        }

        return $next($request);
    }
}
