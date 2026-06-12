<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiError implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $threadId,
        public string $error
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('chat.' . $this->threadId);
    }

    public function broadcastAs(): string
    {
        return 'ai.error';
    }
}