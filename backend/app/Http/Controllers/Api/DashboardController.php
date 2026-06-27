<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;

        $statusCounts = Ticket::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $priorityCounts = Ticket::selectRaw('priority, count(*) as count')
            ->groupBy('priority')
            ->pluck('count', 'priority');

        $agentWorkload = User::where('organization_id', $orgId)
            ->whereIn('role', ['admin', 'agent'])
            ->withCount(['tickets as open_tickets' => function ($q) {
                $q->whereIn('status', ['open', 'pending']);
            }])
            ->get(['id', 'name', 'email', 'open_tickets']);

        $recentTickets = Ticket::with('assignee:id,name')
            ->latest()
            ->take(5)
            ->get(['id', 'subject', 'status', 'priority', 'created_at', 'assigned_to', 'response_due_at', 'resolution_due_at', 'responded_at', 'resolved_at']);

        // Extra Metrics
        // 1. Avg First Response Time in minutes
        $respondedTickets = Ticket::whereNotNull('responded_at')->get(['created_at', 'responded_at']);
        $totalResponseTime = 0;
        foreach ($respondedTickets as $t) {
            $totalResponseTime += $t->created_at->diffInMinutes($t->responded_at);
        }
        $avgResponseTime = $respondedTickets->count() > 0 ? ($totalResponseTime / $respondedTickets->count()) : 0;

        // 2. SLA breach rate
        $allTickets = Ticket::all(['status', 'created_at', 'response_due_at', 'resolution_due_at', 'responded_at', 'resolved_at']);
        $totalTickets = $allTickets->count();
        $breachedCount = 0;
        $now = now();
        foreach ($allTickets as $t) {
            $isBreached = false;
            if ($t->responded_at && $t->response_due_at && $t->responded_at->gt($t->response_due_at)) {
                $isBreached = true;
            } elseif (!$t->responded_at && $t->response_due_at && $now->gt($t->response_due_at)) {
                $isBreached = true;
            }

            if ($t->resolved_at && $t->resolution_due_at && $t->resolved_at->gt($t->resolution_due_at)) {
                $isBreached = true;
            } elseif (!in_array($t->status, ['resolved', 'closed']) && !$t->resolved_at && $t->resolution_due_at && $now->gt($t->resolution_due_at)) {
                $isBreached = true;
            }

            if ($isBreached) {
                $breachedCount++;
            }
        }
        $slaBreachRate = $totalTickets > 0 ? round(($breachedCount / $totalTickets) * 100, 1) : 0;

        // 3. Tickets created per day (last 7 days)
        $recentCreatedTickets = Ticket::where('created_at', '>=', now()->subDays(6)->startOfDay())->get(['created_at']);
        $ticketsCreatedPerDay = [];
        foreach ($recentCreatedTickets as $t) {
            $dateStr = $t->created_at->format('Y-m-d');
            if (!isset($ticketsCreatedPerDay[$dateStr])) {
                $ticketsCreatedPerDay[$dateStr] = 0;
            }
            $ticketsCreatedPerDay[$dateStr]++;
        }

        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $dateStr = now()->subDays($i)->format('Y-m-d');
            $dailyStats[] = [
                'date' => $dateStr,
                'count' => $ticketsCreatedPerDay[$dateStr] ?? 0,
            ];
        }

        return response()->json([
            'by_status'          => $statusCounts,
            'by_priority'        => $priorityCounts,
            'agent_workload'     => $agentWorkload,
            'recent_tickets'     => $recentTickets,
            'total'              => $totalTickets,
            'avg_response_time'  => round($avgResponseTime, 1),
            'sla_breach_rate'    => $slaBreachRate,
            'tickets_by_day'     => $dailyStats,
        ]);
    }

    public function agents(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;
        $agents = User::where('organization_id', $orgId)
            ->whereIn('role', ['admin', 'agent'])
            ->get(['id', 'name', 'email', 'role']);
        return response()->json($agents);
    }

    public function customers(Request $request): JsonResponse
    {
        $orgId = $request->user()->organization_id;
        $customers = User::where('organization_id', $orgId)
            ->where('role', 'customer')
            ->get(['id', 'name', 'email']);
        return response()->json($customers);
    }
}
