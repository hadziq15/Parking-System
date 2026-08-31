<?php

use App\Models\Log;
use App\Models\User;

test('user can only see their own activity log', function () {
    $user = User::factory()->create(['role' => 'user']);
    $otherUser = User::factory()->create(['role' => 'user']);

    Log::create([
        'user_id' => $user->id,
        'action' => 'User logged in to their dashboard',
    ]);

    Log::create([
        'user_id' => $otherUser->id,
        'action' => 'Other user changed parking settings',
    ]);

    $response = $this->actingAs($user)->get(route('logs.index'));

    $response->assertOk();
    $response->assertSee('User logged in to their dashboard');
    $response->assertDontSee('Other user changed parking settings');
});

test('admin can see all activity logs', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'user']);

    Log::create([
        'user_id' => $user->id,
        'action' => 'User entered a vehicle',
    ]);

    Log::create([
        'user_id' => $admin->id,
        'action' => 'Admin updated vehicle pricing',
    ]);

    $response = $this->actingAs($admin)->get(route('logs.admin.index'));

    $response->assertOk();
    $response->assertSee('User entered a vehicle');
    $response->assertSee('Admin updated vehicle pricing');
});
