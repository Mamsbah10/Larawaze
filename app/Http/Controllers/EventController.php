<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Notification;
class EventController extends Controller
{
    public function index(Request $request)
    {
        // Retourne les événements valides (non expirés)
        $now = Carbon::now();
        $events = Event::with('votes')->where(function($q) use($now) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', $now);
        })->get();

        return response()->json($events);
    }

   // EventController.php

public function store(Request $request)
{
    // 1. Validation : Accepte maintenant la 'description'
    $data = $request->validate([
        'type' => 'required|string',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        // Max 1000 caractères, nullable
        'description' => 'nullable|string|max:1000', 
    ]);

    $userId = $request->session()->get('user_id');

    // 2. Anti-doublon (5 minutes)
    $exists = Event::where('user_id', $userId)
        ->where('type', $data['type'])
        ->whereBetween('created_at', [now()->subMinutes(5), now()])
        ->exists();

    if ($exists) {
        return response()->json(['error' => 'Déjà signalé récemment'], 403);
    }

    // 3. Création du signalement avec expiration 1 minute
    $event = Event::create([
        'user_id' => $userId,
        'type' => $data['type'],
        'latitude' => $data['latitude'],
        'longitude' => $data['longitude'],
        // Utilise la description fournie
        'description' => $data['description'] ?? null, 
        'expires_at' => now()->addMinute(), 
    ]);
    
    // 4. Création de la notification
    Notification::create([
        'type' => 'event',
        'message' => "Nouveau signalement : {$event->type}",
        'user_id' => null, // null = tous les utilisateurs
    ]);

    return response()->json(['success' => true, 'event' => $event]);
}

}
