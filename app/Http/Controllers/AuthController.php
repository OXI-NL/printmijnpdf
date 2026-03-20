<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Toon login pagina
     */
    public function showLogin()
    {
        // Als al ingelogd, redirect naar admin
        if (session('admin_authenticated')) {
            return redirect()->route('admin.orders');
        }

        return view('admin.login');
    }

    /**
     * Verwerk login
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Hardcoded credentials (in productie beter via .env of database)
        $validUsername = config('admin.username', 'Printoperator_3');
        $validPassword = config('admin.password', '#AeG$%^print_2026!');

        if ($request->input('username') === $validUsername && 
            $request->input('password') === $validPassword) {
            
            session(['admin_authenticated' => true]);
            session(['admin_username' => $validUsername]);
            
            return redirect()->route('admin.orders');
        }

        return back()->withErrors([
            'login' => 'Ongeldige gebruikersnaam of wachtwoord.',
        ])->withInput($request->only('username'));
    }

    /**
     * Uitloggen
     */
    public function logout(Request $request)
    {
        session()->forget('admin_authenticated');
        session()->forget('admin_username');
        
        return redirect()->route('admin.login')->with('success', 'Je bent uitgelogd.');
    }
}
