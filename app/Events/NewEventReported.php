<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewEventReported implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public $eventData;

    /**
     * Créez une nouvelle instance d'événement.
     * @return void
     */
    public function __construct($event) // Recevoir les données de l'événement
    {
        $this->eventData = $event;
    }

    /**
     * Obtenir les canaux sur lesquels l'événement doit diffuser.
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Canal public pour que tout le monde reçoive l'alerte
        return [
            new Channel('public-events'),
        ];
    }

    /**
     * Le nom de l'événement diffusé (écouté côté client).
     */
    public function broadcastAs()
    {
        return 'event.created';
    }
    
}