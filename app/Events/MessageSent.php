<?php

namespace App\Events;

use App\Models\Message;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $sender;
    public $receiver;
    public $unread_messages;

    public function __construct(Message $message, User $sender, User $receiver, $unread_messages)
    {
        $this->message = $message;
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->unread_messages = $unread_messages;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat'); // Canal privé
    }



    public function broadcastWith()
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'receiver_id' => $this->message->receiver_id,
                'content' => $this->message->content,
                'created_at' => $this->message->created_at,
                'updated_at' => $this->message->updated_at,
                'is_read' => $this->message->is_read,
                'product_id' => $this->message->product_id,
                'product' => $this->message->product ? [
                    'id' => $this->message->product->id,
                    'nom' => $this->message->product->nom,
                ] : null,
                'sender' => [
                    'id' => $this->sender->id,
                    'nom' => $this->sender->nom,
                ],
                'receiver' => [
                    'id' => $this->receiver->id,
                    'nom' => $this->receiver->nom,
                ],
            ],
            'unread_messages' => $this->unread_messages,
        ];
    }
}