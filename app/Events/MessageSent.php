<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Request;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $message;
    public $unread_messages;

    public function __construct($message)
    {
        $this->message= $message;

        $this->unread_messages = Message::where('receiver_id', $message->receiver_id)
            ->where('is_read', false)
            ->count();
        \Log::info('Event MessageSent déclenché avec : ');
    }
    public function broadcastOn() : array
    {
        // \Log::info("Événement MessageSent déclenché", ['message' => 'broad']);

        // // dd($this->message);
        return [
            // new PrivateChannel("chat.{$this->message->receiver_id}"),
            new Channel("public-channel"),

            // new PrivateChannel('chat.' . $this->message->sender_id),
        ];
        

        // return ['public-channel'];
    }

    public function broadcastWith()
    {
        // return ['message' => $this->message->load('sender')];
        return [
            'message' => $this->message, // string ou tableau simple
            'sender_id' => \Auth::id(),
            'unread_messages' => $this->unread_messages,

        ];
    }

    public function broadcastAs()
    {

        return 'message.sent';
    }
}