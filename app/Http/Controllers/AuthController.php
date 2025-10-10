<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->role == 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->role == 'dokter'){
                return redirect()->route('dokter.dashboard');
            } else {
                return redirect()->route('pasien.dashboard');
            }
        }
        return back()->withErrors(['email' => 'Email atau Password Salah !']);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'nama' =>  ['required', 'string', 'max:255'],
            'alamat' =>  ['required', 'string', 'max:255'],
            'no_ktp' =>  ['required', 'string', 'max:30'],
            'no_hp' =>  ['required', 'string', 'max:20'],
            'email' =>  ['required', 'string', 'email','max:255', 'unique:user.email'],
            'password' =>  ['required', 'confirmed'],
        ]);

        User::create([
            'nama' => $request->nama,
            'alamat' => $request->alamat,
            'no_ktp' => $request->no_ktp,
            'no_hp' => $request->no_hp,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'pasien',
        ]);
        return redirect()->route('login')->with('success', 'Registrasi berhasil! Silakan login.');
    }
    public function logout()
    {
        Auth::logout();
        return redirect()->route('login');
    }
}
