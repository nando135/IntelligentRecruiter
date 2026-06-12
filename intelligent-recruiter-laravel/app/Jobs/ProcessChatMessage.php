<?php

namespace App\Jobs;

use App\Events\AiError;
use App\Events\AiMessage;
use App\Events\AiTyping;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcessChatMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;
    public int $tries   = 1;

    public function __construct(
        public string $threadId,
        public string $message,
        public int    $threadDbId
    ) {}

    public function handle(): void
    {
        $pythonUrl = rtrim(env('PYTHON_AI_URL', 'http://127.0.0.1:8001'), '/');

        broadcast(new AiTyping($this->threadId, true));

        try {
            $response = Http::timeout(115)->post($pythonUrl . '/chat', [
                'thread_id' => $this->threadId,
                'message'   => $this->message,
            ]);

            broadcast(new AiTyping($this->threadId, false));

            if (! $response->successful()) {
                broadcast(new AiError($this->threadId, 'AI service returned an error.'));
                return;
            }

            $reply = $response->json('reply') ?? 'No response generated.';

            $msg = ChatMessage::create([
                'chat_thread_id' => $this->threadDbId,
                'thread_id'      => $this->threadId,
                'role'           => 'ai',
                'content'        => $reply,
            ]);

            $thread = ChatThread::find($this->threadDbId);
            if ($thread && $thread->title === 'New chat') {
                $thread->update(['title' => substr($this->message, 0, 60)]);
            }

            broadcast(new AiMessage($this->threadId, $reply, (string) $msg->id));

        } catch (\Exception $e) {
            broadcast(new AiTyping($this->threadId, false));
            broadcast(new AiError($this->threadId, 'Connection error. Please try again.'));
            Log::error('ProcessChatMessage failed', [
                'thread_id' => $this->threadId,
                'error'     => $e->getMessage(),
            ]);
        }
    }
}