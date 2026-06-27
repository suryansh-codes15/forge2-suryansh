<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    private Organization $org;
    private User $admin;
    private User $agent;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->org = Organization::create([
            'name' => 'Acme Corp',
            'slug' => 'acme-corp',
        ]);

        $this->admin = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Admin User',
            'email' => 'admin@acme.corp',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->agent = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Agent User',
            'email' => 'agent@acme.corp',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        $this->customer = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Customer User',
            'email' => 'customer@acme.corp',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // Seed some SLA Policies
        SlaPolicy::create([
            'organization_id' => $this->org->id,
            'priority' => 'high',
            'response_time_hours' => 4,
            'resolution_time_hours' => 12,
        ]);
    }

    public function test_ticket_creation_resolves_sla_due_dates(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $res = $this->postJson('/api/tickets', [
            'subject' => 'Urgent issue',
            'description' => 'Something crashed.',
            'priority' => 'high',
            'requester_id' => $this->customer->id,
        ]);

        $res->assertStatus(201);
        
        $ticket = Ticket::find($res->json('id'));
        $this->assertNotNull($ticket->response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);

        // Verify high priority SLA (4h response, 12h resolution)
        $diffResponse = $ticket->created_at->diffInHours($ticket->response_due_at);
        $diffResolution = $ticket->created_at->diffInHours($ticket->resolution_due_at);

        $this->assertEquals(4, $diffResponse);
        $this->assertEquals(12, $diffResolution);
    }

    public function test_updating_priority_updates_sla_due_dates(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $ticket = Ticket::create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'subject' => 'My issue',
            'description' => 'Details here',
            'priority' => 'medium',
        ]);

        $initialResponseDue = $ticket->response_due_at;

        // Change priority to high
        $res = $this->patchJson("/api/tickets/{$ticket->id}", [
            'priority' => 'high',
        ]);

        $res->assertStatus(200);

        $ticket->refresh();
        $this->assertNotEquals($initialResponseDue->toDateTimeString(), $ticket->response_due_at->toDateTimeString());
        $this->assertEquals(4, $ticket->created_at->diffInHours($ticket->response_due_at));
    }

    public function test_assignment_triggers_notification(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $ticket = Ticket::create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'subject' => 'Assign me',
            'description' => 'Please assign',
        ]);

        $res = $this->patchJson("/api/tickets/{$ticket->id}", [
            'assigned_to' => $this->agent->id,
        ]);

        $res->assertStatus(200);

        $notification = Notification::where('user_id', $this->agent->id)->first();
        $this->assertNotNull($notification);
        $this->assertEquals('assigned', $notification->type);
        $this->assertStringContainsString('assigned to you', $notification->message);
    }

    public function test_reply_sets_responded_at_and_notifies_customer(): void
    {
        $ticket = Ticket::create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'subject' => 'Assistance needed',
            'description' => 'Detail desc',
        ]);

        $this->actingAs($this->agent, 'sanctum');

        $res = $this->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'I am looking into this now.',
            'type' => 'reply',
        ]);

        $res->assertStatus(201);

        $ticket->refresh();
        $this->assertNotNull($ticket->responded_at);

        // Notification should be sent to customer
        $notification = Notification::where('user_id', $this->customer->id)->first();
        $this->assertNotNull($notification);
        $this->assertEquals('replied', $notification->type);
    }

    public function test_dashboard_stats_computes_average_response_and_sla_breach(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        // Create a ticket that breached response SLA
        $ticket1 = Ticket::create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'subject' => 'Breached ticket',
            'description' => 'Description',
            'priority' => 'high', // Response SLA: 4 hours
        ]);
        // Mock created_at to 5 hours ago
        $ticket1->created_at = now()->subHours(5);
        $ticket1->save();
        $ticket1->resolveSlaTimes();
        $ticket1->save();

        // Create a ticket that is met
        $ticket2 = Ticket::create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'subject' => 'Met ticket',
            'description' => 'Description',
            'priority' => 'high',
        ]);
        $ticket2->created_at = now()->subHours(2);
        $ticket2->responded_at = now()->subHours(1); // response delay: 1 hour (less than 4 hours SLA)
        $ticket2->save();
        $ticket2->resolveSlaTimes();
        $ticket2->save();

        $res = $this->getJson('/api/dashboard/stats');

        $res->assertStatus(200)
            ->assertJsonStructure([
                'avg_response_time',
                'sla_breach_rate',
                'tickets_by_day',
            ]);

        // SLA Breach rate: 1 breached out of 2 tickets = 50%
        $this->assertEquals(50, $res->json('sla_breach_rate'));

        // Average response time: 1 ticket has responded_at, difference is 60 minutes
        $this->assertEquals(60, $res->json('avg_response_time'));
    }

    public function test_role_based_policy_authorization(): void
    {
        $ticket = Ticket::create([
            'organization_id' => $this->org->id,
            'requester_id' => $this->customer->id,
            'subject' => 'Auth test ticket',
            'description' => 'Security check',
        ]);

        // Customer User tries to view their own ticket (200)
        $this->actingAs($this->customer, 'sanctum');
        $this->getJson("/api/tickets/{$ticket->id}")->assertStatus(200);

        // Create another customer
        $otherCustomer = User::create([
            'organization_id' => $this->org->id,
            'name' => 'Other Customer',
            'email' => 'other@acme.corp',
            'password' => bcrypt('password'),
            'role' => 'customer',
        ]);

        // Other Customer User tries to view Customer User's ticket (403)
        $this->actingAs($otherCustomer, 'sanctum');
        $this->getJson("/api/tickets/{$ticket->id}")->assertStatus(403);

        // Agent can view Customer User's ticket (200)
        $this->actingAs($this->agent, 'sanctum');
        $this->getJson("/api/tickets/{$ticket->id}")->assertStatus(200);

        // Agent tries to delete the ticket (403)
        $this->deleteJson("/api/tickets/{$ticket->id}")->assertStatus(403);

        // Admin can delete the ticket (200)
        $this->actingAs($this->admin, 'sanctum');
        $this->deleteJson("/api/tickets/{$ticket->id}")->assertStatus(200);
        $this->assertNull(Ticket::find($ticket->id));
    }
}
