<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use App\Models\Event;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function vote(Request $request, Event $event)
    {
        $data = $request->validate([
            'type' => 'required|in:up,down'
        ]);
        $userId = $request->session()->get('user_id');

        // update or create
        $vote = Vote::updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $userId],
            ['type' => $data['type']]
        );

        // Logique simple : si beaucoup de downvotes => supprimer l'event
        $downCount = $event->votes()->where('type','down')->count();
        $upCount = $event->votes()->where('type','up')->count();

        if ($downCount > $upCount + 3) { // règle simple
            $event->delete();
            return response()->json(['deleted' => true]);
        }

        return response()->json(['success' => true, 'vote' => $vote]);
    }
}
