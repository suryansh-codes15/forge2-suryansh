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
            ->get(['id', 'subject', 'status', 'priority', 'created_at', 'assigned_to']);

        return response()->json([
            'by_status'      => $statusCounts,
            'by_priority'    => $priorityCounts,
            'agent_workload' => $agentWorkload,
            'recent_tickets' => $recentTickets,
            'total'          => Ticket::count(),
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
