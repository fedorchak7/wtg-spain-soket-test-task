<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\MessageService;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private MessageService $messageService) {}

    public function index(): Response
    {
        $authUser = auth()->user();

        $users = User::where('id', '!=', $authUser->id)->get();
        $unreadCounts = $this->messageService->unreadCounts($authUser);

        $users->each(function ($user) use ($unreadCounts) {
            $user->unread_count = $unreadCounts->get($user->id, 0);
        });

        return Inertia::render('Dashboard', [
            'users' => UserResource::collection($users),
            'authUser' => new UserResource($authUser),
        ]);
    }
}
