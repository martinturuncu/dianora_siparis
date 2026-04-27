<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        // If already logged in, redirect to home
        if (session('is_admin') === true) {
            return redirect('/');
        }
        
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        $storedHash = env('ADMIN_PASSWORD_HASH');

        if (!$storedHash) {
            return back()->withErrors(['password' => 'Sistemde şifre tanımlı değil. Lütfen yönetici ile iletişime geçin.']);
        }

        if (Hash::check($request->password, $storedHash)) {
            $request->session()->put('is_admin', true);
            $request->session()->regenerate();
            
            return redirect()->intended('/');
        }

        return back()->withErrors(['password' => 'Hatalı şifre girdiniz.']);
    }

    public function logout(Request $request)
    {
        $request->session()->forget('is_admin');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
