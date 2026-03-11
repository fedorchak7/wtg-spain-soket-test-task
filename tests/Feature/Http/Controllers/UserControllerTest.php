// tests/Feature/Http/Controllers/UserControllerTest.php

<?php

use App\Models\Message;
use App\Models\User;

it('shows dashboard with users and unread counts', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Message::factory()->count(3)->create([
        'sender_id' => $other->id,
        'receiver_id' => $user->id,
        'read_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('users')
            ->has('authUser')
        );
});

it('requires authentication', function () {
    $this->get(route('dashboard'))
        ->assertRedirect(route('login'));
});
