<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\User;
use App\Services\MessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private MessageService $messageService) {}

    public function index(Request $request, User $user): JsonResponse
    {
        $page = $request->integer('page', 1);

        if ($page === 1) {
            $this->messageService->markAsRead(auth()->user(), $user);
        }
        $result = $this->messageService->conversation(auth()->user(), $user, $page);

        return response()->json([
            'data' => MessageResource::collection($result->messages),
            'meta' => [
                'has_more' => $result->hasMore,
            ],
        ]);
    }

    public function store(SendMessageRequest $request, User $user): JsonResponse
    {
        $message = $this->messageService->send(
            auth()->user(),
            $user,
            $request->validated('text')
        );

        return (new MessageResource($message))
            ->response()
            ->setStatusCode(201);
    }
}
