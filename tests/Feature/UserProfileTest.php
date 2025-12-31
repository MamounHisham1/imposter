<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\GameHistory;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_their_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('my-profile'))
            ->assertStatus(200)
            ->assertSeeLivewire('user-profile');
    }

    public function test_authenticated_user_can_view_another_users_profile(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->get(route('user.profile', $otherUser))
            ->assertStatus(200)
            ->assertSeeLivewire('user-profile');
    }

    public function test_profile_displays_user_stats(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        GameHistory::factory()->count(5)->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'won' => true,
        ]);

        GameHistory::factory()->count(3)->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'won' => false,
        ]);

        Livewire::actingAs($user)
            ->test('user-profile', ['user' => $user])
            ->assertSee('8');
    }

    public function test_profile_displays_game_history(): void
    {
        $user = User::factory()->create();
        $room = Room::factory()->create();

        $history = GameHistory::factory()->create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'was_imposter' => true,
            'won' => true,
            'score' => 100,
        ]);

        Livewire::actingAs($user)
            ->test('user-profile', ['user' => $user])
            ->assertSee('100');
    }

    public function test_user_can_send_friend_request(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        Livewire::actingAs($user)
            ->test('user-profile', ['user' => $friend])
            ->call('sendFriendRequest')
            ->assertDispatched('friend-request-sent');

        $this->assertDatabaseHas('friendships', [
            'user_id' => $user->id,
            'friend_id' => $friend->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_cannot_send_friend_request_to_self(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test('user-profile', ['user' => $user])
            ->call('sendFriendRequest');

        $this->assertDatabaseMissing('friendships', [
            'user_id' => $user->id,
            'friend_id' => $user->id,
        ]);
    }

    public function test_user_can_view_friends_list(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        Friendship::factory()->accepted()->create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
        ]);

        $this->actingAs($user)
            ->get(route('friends'))
            ->assertStatus(200)
            ->assertSeeLivewire('friends')
            ->assertSee($friend->name);
    }

    public function test_user_can_remove_friend(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        $friendship = Friendship::factory()->accepted()->create([
            'user_id' => $user->id,
            'friend_id' => $friend->id,
        ]);

        Livewire::actingAs($user)
            ->test('friends')
            ->call('removeFriend', $friendship->id)
            ->assertDispatched('friend-removed');

        $this->assertDatabaseMissing('friendships', [
            'id' => $friendship->id,
        ]);
    }

    public function test_user_can_accept_friend_request(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        $friendship = Friendship::factory()->pending()->create([
            'user_id' => $friend->id,
            'friend_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test('friend-requests')
            ->call('acceptRequest', $friendship->id)
            ->assertDispatched('friend-request-accepted');

        $this->assertDatabaseHas('friendships', [
            'id' => $friendship->id,
            'status' => 'accepted',
        ]);
    }

    public function test_user_can_decline_friend_request(): void
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();

        $friendship = Friendship::factory()->pending()->create([
            'user_id' => $friend->id,
            'friend_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test('friend-requests')
            ->call('declineRequest', $friendship->id)
            ->assertDispatched('friend-request-declined');

        $this->assertDatabaseHas('friendships', [
            'id' => $friendship->id,
            'status' => 'declined',
        ]);
    }

    public function test_guest_cannot_view_profile_pages(): void
    {
        $this->get(route('my-profile'))
            ->assertRedirect(route('login'));

        $this->get(route('friends'))
            ->assertRedirect(route('login'));

        $this->get(route('friend-requests'))
            ->assertRedirect(route('login'));
    }
}
