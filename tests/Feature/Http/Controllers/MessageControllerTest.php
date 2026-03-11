<?php

use App\Models\Message;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
});

describe('GET /messages/{user}', function () {
    it('returns conversation messages', function () {
        Message::factory()->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
            'text' => 'Hello',
        ]);

        $this->actingAs($this->user)
            ->getJson(route('messages.index', $this->otherUser))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.text', 'Hello')
            ->assertJsonStructure([
                'data' => [['id', 'sender_id', 'receiver_id', 'text', 'sender_name', 'created_at']],
                'meta' => ['has_more'],
            ]);
    });

    it('marks messages as read when opening conversation', function () {
        $message = Message::factory()->create([
            'sender_id' => $this->otherUser->id,
            'receiver_id' => $this->user->id,
            'read_at' => null,
        ]);

        $this->actingAs($this->user)
            ->getJson(route('messages.index', $this->otherUser))
            ->assertOk();

        expect($message->fresh()->read_at)->not->toBeNull();
    });

    it('paginates results', function () {
        Message::factory()->count(30)->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
        ]);

        $this->actingAs($this->user)
            ->getJson(route('messages.index', $this->otherUser) . '?page=1')
            ->assertOk()
            ->assertJsonCount(25, 'data')
            ->assertJsonPath('meta.has_more', true);
    });

    it('requires authentication', function () {
        $this->getJson(route('messages.index', $this->otherUser))
            ->assertUnauthorized();
    });
});

describe('POST /messages/{user}', function () {
    it('sends a message and returns 201', function () {
        $this->actingAs($this->user)
            ->postJson(route('messages.store', $this->otherUser), [
                'text' => 'Hello!',
            ])
            ->assertCreated()
            ->assertJsonPath('data.text', 'Hello!')
            ->assertJsonStructure([
                'data' => ['id', 'sender_id', 'receiver_id', 'text', 'sender_name', 'created_at'],
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
            'text' => 'Hello!',
        ]);
    });

    it('validates text is required', function () {
        $this->actingAs($this->user)
            ->postJson(route('messages.store', $this->otherUser), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');
    });

    it('validates text max length', function () {
        $this->actingAs($this->user)
            ->postJson(route('messages.store', $this->otherUser), [
                'text' => str_repeat('a', 5001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('text');
    });

    it('forbids sending a message to yourself', function () {
        $this->actingAs($this->user)
            ->postJson(route('messages.store', $this->user), [
                'text' => 'Hello me!',
            ])
            ->assertForbidden();
    });

    it('requires authentication', function () {
        $this->postJson(route('messages.store', $this->otherUser), [
            'text' => 'Hello!',
        ])->assertUnauthorized();
    });
});
