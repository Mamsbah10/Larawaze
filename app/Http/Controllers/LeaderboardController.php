<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index()
    {
        $users = User::withCount('events')
            ->withCount([
                'votes as up_votes_count' => function ($q) {
                    $q->where('type', 'up');
                }
            ])
            ->orderByDesc('events_count')
            ->orderByDesc('up_votes_count')
            ->take(10)
            ->get();

        return view('leaderboard', compact('users'));
    }
}
