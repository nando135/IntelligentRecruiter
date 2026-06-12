<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $threadId,
        public string $message,
        public string $messageId
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('chat.' . $this->threadId);
    }

    public function broadcastAs(): string
    {
        return 'ai.message';
    }
}