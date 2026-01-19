<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Event;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        // page d'accueil basique
        return redirect()->route('map');
    }

    public function map(Request $request)
    {
        $user = null;
        if ($request->session()->has('user_id')) {
            $user = \App\Models\User::find($request->session()->get('user_id'));
        }
        return view('map', compact('user'));
    }
}
