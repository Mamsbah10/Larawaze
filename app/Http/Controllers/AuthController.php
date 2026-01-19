<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Afficher le formulaire d'inscription
    public function showRegister()
    {
        return view('auth.register');
    }

    // Enregistrer l'utilisateur
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user'
        ]);

        session(['user_id' => $user->id]);

        return redirect()->route('map');
    }

    // Afficher le formulaire de connexion
    public function showLogin()
    {
        return view('auth.login');
    }

    // Connexion utilisateur
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Identifiants incorrects']);
        }

        session(['user_id' => $user->id]);

        return redirect()->route('map');
    }

    // Déconnexion
    public function logout(Request $request)
    {
        $request->session()->forget('user_id');
        return redirect()->route('login.form');
    }
}
