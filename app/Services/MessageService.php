<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use App\Services\DTOs\PaginatedMessages;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class MessageService
{
    public function send(User $sender, User $receiver, string $text): Message
    {
        if ($sender->id === $receiver->id) {
            throw new InvalidArgumentException('Cannot send a message to yourself.');
        }

        $message = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'text' => $text,
        ]);

        $message->load('sender');

        broadcast(new MessageSent($message))->toOthers();

        return $message;
    }

    public function conversation(User $user1, User $user2, int $page = 1, int $perPage = 25): PaginatedMessages
    {
        $messages = Message::query()
            ->where(function ($q) use ($user1, $user2) {
                $q->where('sender_id', $user1->id)
                    ->where('receiver_id', $user2->id);
            })
            ->orWhere(function ($q) use ($user1, $user2) {
                $q->where('sender_id', $user2->id)
                    ->where('receiver_id', $user1->id);
            })
            ->with('sender')
            ->orderByDesc('created_at')
            ->skip(($page - 1) * $perPage)
            ->take($perPage + 1)
            ->get();

        return new PaginatedMessages(
            messages: $messages->take($perPage)->reverse()->values(),
            hasMore: $messages->count() > $perPage,
        );
    }

    public function markAsRead(User $receiver, User $sender): int
    {
        return Message::where('receiver_id', $receiver->id)
            ->where('sender_id', $sender->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function unreadCounts(User $user): Collection
    {
        return Message::where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->selectRaw('sender_id, COUNT(*) as count')
            ->groupBy('sender_id')
            ->pluck('count', 'sender_id');
    }
}
