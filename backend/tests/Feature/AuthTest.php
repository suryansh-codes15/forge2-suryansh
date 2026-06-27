<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_register_and_login(): void
    {
        // Register
        $res = $this->postJson('/api/auth/register', [
            'company'  => 'Test Corp',
            'name'     => 'Test Admin',
            'email'    => 'admin@test.corp',
            'password' => 'password',
        ]);

        $res->assertStatus(201)
            ->assertJsonStructure([
                'token',
                'user'         => ['id', 'name', 'email', 'role', 'organization_id'],
                'organization' => ['id', 'name', 'slug'],
            ]);

        $this->assertEquals('admin', $res->json('user.role'));
        $this->assertNotNull(Organization::first());

        // Login
        $login = $this->postJson('/api/auth/login', [
            'email'    => 'admin@test.corp',
            'password' => 'password',
        ]);

        $login->assertStatus(200)
              ->assertJsonStructure(['token', 'user', 'organization']);
    }

    public function test_can_create_and_list_tickets(): void
    {
        $org  = Organization::factory()->create();
        $user = User::factory()->create(['organization_id' => $org->id, 'role' => 'admin']);

        $this->actingAs($user, 'sanctum');

        // Create ticket
        $create = $this->postJson('/api/tickets', [
            'subject'     => 'Test ticket',
            'description' => 'Something is broken.',
            'priority'    => 'high',
        ]);

        $create->assertStatus(201)
               ->assertJsonPath('subject', 'Test ticket')
               ->assertJsonPath('organization_id', $org->id);

        // List tickets
        $list = $this->getJson('/api/tickets');
        $list->assertStatus(200)
             ->assertJsonPath('total', 1);
    }

    public function test_cross_org_access_is_denied(): void
    {
        $orgA  = Organization::factory()->create();
        $orgB  = Organization::factory()->create();
        $userA = User::factory()->create(['organization_id' => $orgA->id]);
        $userB = User::factory()->create(['organization_id' => $orgB->id]);

        // UserA creates a ticket
        $this->actingAs($userA, 'sanctum');
        $ticket = $this->postJson('/api/tickets', [
            'subject'     => 'OrgA ticket',
            'description' => 'Private to Org A.',
            'priority'    => 'low',
        ])->json('id');

        // UserB cannot read it
        $this->actingAs($userB, 'sanctum');
        $this->getJson("/api/tickets/{$ticket}")->assertStatus(403);
    }
}
