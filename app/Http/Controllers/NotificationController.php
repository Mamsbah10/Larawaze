<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        return Event::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }
}
