<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessChatMessage;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    private string $pythonUrl;

    public function __construct()
    {
        $this->pythonUrl = rtrim(env('PYTHON_AI_URL', 'http://127.0.0.1:8001'), '/');
    }

    public function newThread(Request $request): JsonResponse
    {
        try {
            $response = Http::timeout(10)->get($this->pythonUrl . '/chat/new-thread');

            if (! $response->successful()) {
                return response()->json(['error' => 'Python service unavailable.'], 503);
            }

            $threadId = $response->json('thread_id');

            $thread = ChatThread::create([
                'thread_id' => $threadId,
                'title'     => 'New chat',
            ]);

            return response()->json([
                'thread_id'  => $threadId,
                'id'         => $thread->id,
                'title'      => $thread->title,
                'created_at' => $thread->created_at,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'thread_id' => 'required|string',
            'message'   => 'required|string|max:4000',
        ]);

        $threadId = $request->input('thread_id');
        $message  = $request->input('message');

        $thread = ChatThread::where('thread_id', $threadId)->firstOrFail();

        $humanMsg = ChatMessage::create([
            'chat_thread_id' => $thread->id,
            'thread_id'      => $threadId,
            'role'           => 'human',
            'content'        => $message,
        ]);

        ProcessChatMessage::dispatch($threadId, $message, $thread->id);

        return response()->json([
            'status'     => 'queued',
            'thread_id'  => $threadId,
            'message_id' => $humanMsg->id,
        ]);
    }

    public function history(string $threadId): JsonResponse
    {
        $thread   = ChatThread::where('thread_id', $threadId)->firstOrFail();
        $messages = $thread->messages()->get(['role', 'content', 'created_at']);

        return response()->json([
            'thread_id' => $threadId,
            'title'     => $thread->title,
            'messages'  => $messages,
        ]);
    }

    public function threads(): JsonResponse
    {
        $threads = ChatThread::orderByDesc('updated_at')
            ->select(['id', 'thread_id', 'title', 'created_at', 'updated_at'])
            ->get();

        return response()->json(['threads' => $threads]);
    }

    public function deleteThread(string $threadId): JsonResponse
    {
        $thread = ChatThread::where('thread_id', $threadId)->first();
        if ($thread) {
            $thread->delete();
        }

        try {
            Http::timeout(10)->delete($this->pythonUrl . '/chat/' . $threadId);
        } catch (\Exception $e) {
            Log::warning('Could not clear Python thread: ' . $e->getMessage());
        }

        return response()->json(['status' => 'deleted', 'thread_id' => $threadId]);
    }

    public function stats(): JsonResponse
    {
        $reverbHost  = env('REVERB_HOST', '127.0.0.1');
        $reverbPort  = env('REVERB_PORT', 8080);
        $appId       = env('REVERB_APP_ID');
        $appSecret   = env('REVERB_APP_SECRET');

        try {
            $response = Http::timeout(5)
                ->withBasicAuth($appId, $appSecret)
                ->get("http://{$reverbHost}:{$reverbPort}/apps/{$appId}/channels");

            $channels   = $response->json('channels') ?? [];
            $totalUsers = collect($channels)->sum('subscription_count');

            return response()->json([
                'status'          => 'ok',
                'active_channels' => count($channels),
                'total_users'     => $totalUsers,
                'channels'        => $channels,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Could not reach Reverb: ' . $e->getMessage(),
            ], 500);
        }
    }
}