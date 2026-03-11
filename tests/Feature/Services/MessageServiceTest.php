<?php

use App\Models\Message;
use App\Models\User;
use App\Services\DTOs\PaginatedMessages;
use App\Services\MessageService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->service = new MessageService();
    $this->sender = User::factory()->create();
    $this->receiver = User::factory()->create();
});

describe('send', function () {
    it('creates a message and returns it with sender loaded', function () {
        Event::fake();

        $message = $this->service->send($this->sender, $this->receiver, 'Hello!');

        expect($message)
            ->toBeInstanceOf(Message::class)
            ->text->toBe('Hello!')
            ->sender_id->toBe($this->sender->id)
            ->receiver_id->toBe($this->receiver->id)
            ->relationLoaded('sender')->toBeTrue();

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'text' => 'Hello!',
        ]);
    });

    it('broadcasts MessageSent event', function () {
        Event::fake();

        $this->service->send($this->sender, $this->receiver, 'Hello!');

        Event::assertDispatched(\App\Events\MessageSent::class);
    });

    it('throws exception when sending to yourself', function () {
        $this->service->send($this->sender, $this->sender, 'Hello me');
    })->throws(InvalidArgumentException::class, 'Cannot send a message to yourself.');
});

describe('conversation', function () {
    it('returns PaginatedMessages DTO', function () {
        Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
        ]);

        $result = $this->service->conversation($this->sender, $this->receiver);

        expect($result)->toBeInstanceOf(PaginatedMessages::class);
    });

    it('returns messages in both directions', function () {
        Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'text' => 'Hey',
            'created_at' => now()->subMinute(),
        ]);

        Message::factory()->create([
            'sender_id' => $this->receiver->id,
            'receiver_id' => $this->sender->id,
            'text' => 'Hi back',
            'created_at' => now(),
        ]);

        $result = $this->service->conversation($this->sender, $this->receiver);

        expect($result->messages)->toHaveCount(2);
        expect($result->messages->first()->text)->toBe('Hey');
        expect($result->messages->last()->text)->toBe('Hi back');
    });

    it('does not include messages from other conversations', function () {
        $outsider = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
        ]);

        Message::factory()->create([
            'sender_id' => $outsider->id,
            'receiver_id' => $this->sender->id,
        ]);

        $result = $this->service->conversation($this->sender, $this->receiver);

        expect($result->messages)->toHaveCount(1);
    });

    it('returns messages in chronological order', function () {
        Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'created_at' => now()->subMinutes(2),
            'text' => 'first',
        ]);

        Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'created_at' => now(),
            'text' => 'second',
        ]);

        $result = $this->service->conversation($this->sender, $this->receiver);

        expect($result->messages->first()->text)->toBe('first');
        expect($result->messages->last()->text)->toBe('second');
    });

    it('paginates correctly and sets has_more', function () {
        Message::factory()->count(5)->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
        ]);

        $result = $this->service->conversation($this->sender, $this->receiver, page: 1, perPage: 3);

        expect($result->messages)->toHaveCount(3);
        expect($result->hasMore)->toBeTrue();
    });

    it('returns has_more false on last page', function () {
        Message::factory()->count(2)->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
        ]);

        $result = $this->service->conversation($this->sender, $this->receiver, page: 1, perPage: 5);

        expect($result->messages)->toHaveCount(2);
        expect($result->hasMore)->toBeFalse();
    });

    it('eager loads sender relation', function () {
        Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
        ]);

        $result = $this->service->conversation($this->sender, $this->receiver);

        expect($result->messages->first()->relationLoaded('sender'))->toBeTrue();
    });
});

describe('markAsRead', function () {
    it('marks unread messages as read', function () {
        $message = Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'read_at' => null,
        ]);

        $count = $this->service->markAsRead($this->receiver, $this->sender);

        expect($count)->toBe(1);
        expect($message->fresh()->read_at)->not->toBeNull();
    });

    it('does not mark already read messages', function () {
        Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'read_at' => now()->subHour(),
        ]);

        $count = $this->service->markAsRead($this->receiver, $this->sender);

        expect($count)->toBe(0);
    });

    it('only marks messages from specific sender', function () {
        $otherSender = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'read_at' => null,
        ]);

        Message::factory()->create([
            'sender_id' => $otherSender->id,
            'receiver_id' => $this->receiver->id,
            'read_at' => null,
        ]);

        $this->service->markAsRead($this->receiver, $this->sender);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $otherSender->id,
            'read_at' => null,
        ]);
    });

    it('returns zero when no unread messages exist', function () {
        $count = $this->service->markAsRead($this->receiver, $this->sender);

        expect($count)->toBe(0);
    });
});

describe('unreadCounts', function () {
    it('returns unread counts grouped by sender', function () {
        $sender2 = User::factory()->create();

        Message::factory()->count(3)->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'read_at' => null,
        ]);

        Message::factory()->count(2)->create([
            'sender_id' => $sender2->id,
            'receiver_id' => $this->receiver->id,
            'read_at' => null,
        ]);

        $counts = $this->service->unreadCounts($this->receiver);

        expect($counts)->toHaveCount(2);
        expect($counts->get($this->sender->id))->toBe(3);
        expect($counts->get($sender2->id))->toBe(2);
    });

    it('ignores read messages', function () {
        Message::factory()->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'read_at' => now(),
        ]);

        $counts = $this->service->unreadCounts($this->receiver);

        expect($counts)->toBeEmpty();
    });

    it('returns empty collection when no unread messages', function () {
        $counts = $this->service->unreadCounts($this->receiver);

        expect($counts)->toBeEmpty();
    });
});
