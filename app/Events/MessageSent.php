<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Broadcasting\InteractsWithSockets;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message; // Supprimer le typage strict 'string' pour accepter un tableau ou objet
    public $unread_messages;

    public function __construct($message)
    {
        // Vérifier si $message est un tableau et le convertir en objet si nécessaire
        if (is_array($message)) {
            $this->message = (object) $message; // Convertir le tableau en objet standard
        } else {
            $this->message = $message; // Accepter directement un objet (ex. modèle Message)
        }

        // Calculer les messages non lus en fonction de receiver_id
        $this->unread_messages = Message::where('receiver_id', $this->message->receiver_id)
            ->where('is_read', false)
            ->count();

        \Log::info('Event MessageSent déclenché avec : ', ['message' => $this->message]);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel("chat.{$this->message->receiver_id}"), // Utiliser receiver_id comme canal
            // new Channel("public-channel"), // Optionnel, si vous voulez diffuser publiquement
        ];
    }

    public function broadcastWith()
    {
        return [
            'message' => $this->message, // Envoyer l'objet ou les données converties
            'unread_messages' => $this->unread_messages,
        ];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}