<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\Organization;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ──────────────────────────────────────────────
        // 1 Organisation
        // ──────────────────────────────────────────────
        $org = Organization::create([
            'name' => 'Demo Corp',
            'slug' => 'demo-corp',
            'plan' => 'pro',
        ]);

        // ──────────────────────────────────────────────
        // SLA Policies (4 priorities)
        // ──────────────────────────────────────────────
        $slas = [
            ['priority' => 'low',    'response_time_hours' => 24, 'resolution_time_hours' => 72],
            ['priority' => 'medium', 'response_time_hours' => 8,  'resolution_time_hours' => 24],
            ['priority' => 'high',   'response_time_hours' => 4,  'resolution_time_hours' => 12],
            ['priority' => 'urgent', 'response_time_hours' => 1,  'resolution_time_hours' => 4],
        ];
        foreach ($slas as $sla) {
            SlaPolicy::create(array_merge($sla, ['organization_id' => $org->id]));
        }

        // ──────────────────────────────────────────────
        // 1 Admin
        // ──────────────────────────────────────────────
        $admin = User::create([
            'organization_id' => $org->id,
            'name'            => 'Alice Admin',
            'email'           => 'admin@democorp.test',
            'password'        => Hash::make('password'),
            'role'            => 'admin',
        ]);

        // ──────────────────────────────────────────────
        // 2 Agents
        // ──────────────────────────────────────────────
        $agent1 = User::create([
            'organization_id' => $org->id,
            'name'            => 'Bob Agent',
            'email'           => 'bob@democorp.test',
            'password'        => Hash::make('password'),
            'role'            => 'agent',
        ]);
        $agent2 = User::create([
            'organization_id' => $org->id,
            'name'            => 'Carol Agent',
            'email'           => 'carol@democorp.test',
            'password'        => Hash::make('password'),
            'role'            => 'agent',
        ]);

        // ──────────────────────────────────────────────
        // 2 Customers
        // ──────────────────────────────────────────────
        $cust1 = User::create([
            'organization_id' => $org->id,
            'name'            => 'Dave Customer',
            'email'           => 'dave@customer.test',
            'password'        => Hash::make('password'),
            'role'            => 'customer',
        ]);
        $cust2 = User::create([
            'organization_id' => $org->id,
            'name'            => 'Eve Customer',
            'email'           => 'eve@customer.test',
            'password'        => Hash::make('password'),
            'role'            => 'customer',
        ]);

        // ──────────────────────────────────────────────
        // ~12 Tickets with comments and activities
        // ──────────────────────────────────────────────
        $tickets = [
            ['subject' => 'Cannot login to portal',          'priority' => 'urgent', 'status' => 'open',     'requester' => $cust1, 'assignee' => $agent1],
            ['subject' => 'Payment failed on checkout',      'priority' => 'high',   'status' => 'pending',  'requester' => $cust2, 'assignee' => $agent1],
            ['subject' => 'Slow dashboard loading',          'priority' => 'medium', 'status' => 'open',     'requester' => $cust1, 'assignee' => $agent2],
            ['subject' => 'Export CSV not working',          'priority' => 'medium', 'status' => 'resolved', 'requester' => $cust2, 'assignee' => $agent2],
            ['subject' => 'Email notifications not sending', 'priority' => 'high',   'status' => 'open',     'requester' => $cust1, 'assignee' => null],
            ['subject' => 'Password reset link expired',     'priority' => 'medium', 'status' => 'closed',   'requester' => $cust2, 'assignee' => $agent1],
            ['subject' => '2FA not working on mobile',       'priority' => 'high',   'status' => 'pending',  'requester' => $cust1, 'assignee' => $agent2],
            ['subject' => 'Billing invoice missing',         'priority' => 'low',    'status' => 'open',     'requester' => $cust2, 'assignee' => null],
            ['subject' => 'API rate limit too low',          'priority' => 'medium', 'status' => 'open',     'requester' => $cust1, 'assignee' => $agent1],
            ['subject' => 'Dark mode colors incorrect',      'priority' => 'low',    'status' => 'resolved', 'requester' => $cust2, 'assignee' => $agent2],
            ['subject' => 'Cannot delete old tickets',       'priority' => 'medium', 'status' => 'open',     'requester' => $cust1, 'assignee' => $agent1],
            ['subject' => 'Webhook endpoint not firing',     'priority' => 'urgent', 'status' => 'pending',  'requester' => $cust2, 'assignee' => $agent2],
        ];

        foreach ($tickets as $i => $t) {
            $ticket = Ticket::create([
                'organization_id' => $org->id,
                'requester_id'    => $t['requester']->id,
                'assigned_to'     => $t['assignee']?->id,
                'subject'         => $t['subject'],
                'description'     => "This is the full description for: {$t['subject']}. The user is experiencing this issue and needs help from our support team.",
                'status'          => $t['status'],
                'priority'        => $t['priority'],
            ]);

            // Activity: created
            ActivityLog::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $admin->id,
                'action'    => 'created',
                'meta'      => null,
            ]);

            // Activity: assigned (if assigned)
            if ($t['assignee']) {
                ActivityLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id'   => $admin->id,
                    'action'    => 'assigned',
                    'meta'      => ['to' => $t['assignee']->id],
                ]);
            }

            // Add a reply comment for every ticket
            Comment::create([
                'ticket_id' => $ticket->id,
                'user_id'   => $t['assignee']?->id ?? $admin->id,
                'type'      => 'reply',
                'body'      => "Thank you for reaching out! We are looking into this issue and will get back to you shortly.",
            ]);

            // Add internal note for half the tickets
            if ($i % 2 === 0) {
                Comment::create([
                    'ticket_id' => $ticket->id,
                    'user_id'   => $t['assignee']?->id ?? $admin->id,
                    'type'      => 'note',
                    'body'      => "Internal note: Checked logs. Issue seems to be on our end. Escalating to engineering.",
                ]);
            }

            // Status change activity for resolved/closed tickets
            if (in_array($t['status'], ['resolved', 'closed'])) {
                ActivityLog::create([
                    'ticket_id' => $ticket->id,
                    'user_id'   => $t['assignee']?->id ?? $admin->id,
                    'action'    => 'status_changed',
                    'meta'      => ['from' => 'open', 'to' => $t['status']],
                ]);
            }
        }
    }
}
