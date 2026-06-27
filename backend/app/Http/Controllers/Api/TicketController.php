<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Ticket::with(['assignee:id,name,email', 'requester:id,name,email']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhereHas('requester', fn($u) =>
                      $u->where('name', 'like', "%{$request->search}%")
                        ->orWhere('email', 'like', "%{$request->search}%")
                  );
            });
        }

        return response()->json(
            $query->latest()->paginate(20)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject'      => 'required|string|max:200',
            'description'  => 'required|string',
            'priority'     => 'in:low,medium,high,urgent',
            'requester_id' => 'nullable|exists:users,id',
        ]);

        $ticket = Ticket::create([
            'organization_id' => $request->user()->organization_id,
            'requester_id'    => $data['requester_id'] ?? null,
            'subject'         => $data['subject'],
            'description'     => $data['description'],
            'status'          => 'open',
            'priority'        => $data['priority'] ?? 'medium',
        ]);

        $this->logActivity($ticket, $request->user()->id, 'created');

        return response()->json(
            $ticket->load(['assignee:id,name,email', 'requester:id,name,email']),
            201
        );
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket, $request);

        return response()->json(
            $ticket->load([
                'assignee:id,name,email',
                'requester:id,name,email',
                'comments.user:id,name',
                'activities.user:id,name',
            ])
        );
    }

    public function update(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket, $request);

        $data = $request->validate([
            'subject'      => 'sometimes|string|max:200',
            'description'  => 'sometimes|string',
            'status'       => 'sometimes|in:open,pending,resolved,closed',
            'priority'     => 'sometimes|in:low,medium,high,urgent',
            'assigned_to'  => 'sometimes|nullable|exists:users,id',
        ]);

        $old = $ticket->only(['status', 'priority', 'assigned_to']);
        $ticket->update($data);

        if (isset($data['status']) && $data['status'] !== $old['status']) {
            $this->logActivity($ticket, $request->user()->id, 'status_changed', [
                'from' => $old['status'],
                'to'   => $data['status'],
            ]);
        }

        if (array_key_exists('assigned_to', $data) && $data['assigned_to'] !== $old['assigned_to']) {
            $this->logActivity($ticket, $request->user()->id, 'assigned', [
                'to' => $data['assigned_to'],
            ]);
        }

        return response()->json(
            $ticket->load(['assignee:id,name,email', 'requester:id,name,email'])
        );
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorizeTicket($ticket, $request);
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted.']);
    }

    private function authorizeTicket(Ticket $ticket, Request $request): void
    {
        if ($ticket->organization_id !== $request->user()->organization_id) {
            abort(403, 'Access denied.');
        }
    }

    private function logActivity(Ticket $ticket, int $userId, string $action, array $meta = []): void
    {
        ActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $userId,
            'action'    => $action,
            'meta'      => $meta ?: null,
        ]);
    }
}
