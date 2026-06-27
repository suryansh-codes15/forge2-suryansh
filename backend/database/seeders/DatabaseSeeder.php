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
            $tags = [];
            if (str_contains(strtolower($t['subject']), 'login') || str_contains(strtolower($t['subject']), 'password') || str_contains(strtolower($t['subject']), '2fa')) {
                $tags = ['auth', 'security'];
            } elseif (str_contains(strtolower($t['subject']), 'payment') || str_contains(strtolower($t['subject']), 'billing') || str_contains(strtolower($t['subject']), 'invoice')) {
                $tags = ['billing', 'finance'];
            } elseif (str_contains(strtolower($t['subject']), 'api') || str_contains(strtolower($t['subject']), 'webhook')) {
                $tags = ['api', 'integration'];
            } else {
                $tags = ['support'];
            }

            // Staggered creation timestamps
            $daysAgo = 6 - intval($i / 2); // 6, 6, 5, 5, 4, 4, 3, 3, 2, 2, 1, 1
            $hoursAgo = ($i % 2) * 5 + 1;
            $createdAt = now()->subDays($daysAgo)->subHours($hoursAgo)->subMinutes($i * 7);

            // Responded time logic: half are responded quickly, some are slow, some not at all (null)
            $respondedAt = null;
            if ($i !== 4 && $i !== 7 && $i !== 11) {
                // responded within priority limit or slightly over
                $responseDelayMinutes = ($i % 3 === 0) ? ($i * 15 + 10) : ($i * 90 + 30);
                $respondedAt = $createdAt->copy()->addMinutes($responseDelayMinutes);
            }

            // Resolved time logic
            $resolvedAt = null;
            if (in_array($t['status'], ['resolved', 'closed'])) {
                $resolveDelayHours = ($i % 2 === 0) ? ($i + 2) : ($i * 3 + 4);
                $resolvedAt = $createdAt->copy()->addHours($resolveDelayHours);
            }

            $ticket = Ticket::create([
                'organization_id' => $org->id,
                'requester_id'    => $t['requester']->id,
                'assigned_to'     => $t['assignee']?->id,
                'subject'         => $t['subject'],
                'description'     => "This is the full description for: {$t['subject']}. The user is experiencing this issue and needs help from our support team.",
                'status'          => $t['status'],
                'priority'        => $t['priority'],
                'tags'            => $tags,
                'responded_at'    => $respondedAt,
                'resolved_at'     => $resolvedAt,
                'created_at'      => $createdAt,
                'updated_at'      => $resolvedAt ?? $respondedAt ?? $createdAt,
            ]);

            // Re-resolve SLA times with the correct creation date
            $ticket->resolveSlaTimes();
            $ticket->save();

            // Activity: created
            ActivityLog::create([
                'ticket_id'  => $ticket->id,
                'user_id'    => $admin->id,
                'action'     => 'created',
                'meta'       => null,
                'created_at' => $createdAt,
            ]);

            // Activity: assigned (if assigned)
            if ($t['assignee']) {
                ActivityLog::create([
                    'ticket_id'  => $ticket->id,
                    'user_id'    => $admin->id,
                    'action'     => 'assigned',
                    'meta'       => ['to' => $t['assignee']->id],
                    'created_at' => $createdAt->copy()->addMinutes(2),
                ]);
            }

            // Add a reply comment for every ticket
            if ($respondedAt) {
                Comment::create([
                    'ticket_id'  => $ticket->id,
                    'user_id'    => $t['assignee']?->id ?? $admin->id,
                    'type'       => 'reply',
                    'body'       => "Thank you for reaching out! We are looking into this issue and will get back to you shortly.",
                    'created_at' => $respondedAt,
                ]);

                ActivityLog::create([
                    'ticket_id'  => $ticket->id,
                    'user_id'    => $t['assignee']?->id ?? $admin->id,
                    'action'     => 'reply_added',
                    'meta'       => null,
                    'created_at' => $respondedAt,
                ]);
            }

            // Add internal note for half the tickets
            if ($i % 2 === 0) {
                $noteTime = $createdAt->copy()->addHours(1);
                Comment::create([
                    'ticket_id'  => $ticket->id,
                    'user_id'    => $t['assignee']?->id ?? $admin->id,
                    'type'       => 'note',
                    'body'       => "Internal note: Checked logs. Issue seems to be on our end. Escalating to engineering.",
                    'created_at' => $noteTime,
                ]);

                ActivityLog::create([
                    'ticket_id'  => $ticket->id,
                    'user_id'    => $t['assignee']?->id ?? $admin->id,
                    'action'     => 'note_added',
                    'meta'       => null,
                    'created_at' => $noteTime,
                ]);
            }

            // Status change activity for resolved/closed tickets
            if ($resolvedAt) {
                ActivityLog::create([
                    'ticket_id'  => $ticket->id,
                    'user_id'    => $t['assignee']?->id ?? $admin->id,
                    'action'     => 'status_changed',
                    'meta'       => ['from' => 'open', 'to' => $t['status']],
                    'created_at' => $resolvedAt,
                ]);
            }
        }
    }
}
