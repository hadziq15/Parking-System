<?php

use App\Models\User;

test('admin can search users by name or email', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $matchingByName = User::factory()->create([
        'name' => 'Alice Wonderland',
        'email' => 'alice@example.com',
    ]);
    $matchingByEmail = User::factory()->create([
        'name' => 'Bob Tester',
        'email' => 'bobby.search@example.com',
    ]);
    $nonMatching = User::factory()->create([
        'name' => 'Charlie Jordan',
        'email' => 'charlie@example.com',
    ]);

    $response = $this->actingAs($admin)->get(route('user-management.index', ['search' => 'alice']));
    $response->assertOk();
    $response->assertSee($matchingByName->name);
    $response->assertDontSee($matchingByEmail->name);
    $response->assertDontSee($nonMatching->name);

    $response = $this->actingAs($admin)->get(route('user-management.index', ['search' => 'bobby.search@']));
    $response->assertOk();
    $response->assertSee($matchingByEmail->email);
    $response->assertDontSee($matchingByName->email);
    $response->assertDontSee($nonMatching->email);
});
