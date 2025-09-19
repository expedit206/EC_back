<?php

namespace App\Events;

use App\Models\User;
use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

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
        // Canal basé sur les IDs triés
        $channelId = [auth()->id(), $this->receiver->id];
        sort($channelId);
        return new Channel('chat.' . implode('-', $channelId));
    }

    public function broadcastAs()
    {
        return 'MessageSent';
    }

    public function broadcastWith()
    {

        \Log::info('MessageSent broadcast:', [
            // 'message' => $this->message->toArray(),
            // 'sender' => $this->sender->toArray(),
            // 'receiver' => $this->receiver->toArray(),
        ]);
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
            ],
            'sender' => [
                'id' => $this->sender->id,
                'nom' => $this->sender->nom,
            ],
            'receiver' => [
                'id' => $this->receiver->id,
                'nom' => $this->receiver->nom,
            ],
            'unread_messages' => $this->unread_messages,
        ];
    }
}