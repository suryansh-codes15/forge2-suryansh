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
        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }
        if ($request->filled('tag')) {
            $query->whereJsonContains('tags', $request->tag);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('subject', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%")
                  ->orWhere('tags', 'like', "%{$request->search}%")
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
            'tags'         => 'nullable|array',
            'tags.*'       => 'string',
        ]);

        $ticket = Ticket::create([
            'organization_id' => $request->user()->organization_id,
            'requester_id'    => $data['requester_id'] ?? null,
            'subject'         => $data['subject'],
            'description'     => $data['description'],
            'status'          => 'open',
            'priority'        => $data['priority'] ?? 'medium',
            'tags'            => $data['tags'] ?? [],
        ]);

        $this->logActivity($ticket, $request->user()->id, 'created');

        return response()->json(
            $ticket->load(['assignee:id,name,email', 'requester:id,name,email']),
            201
        );
    }

    public function show(Request $request, Ticket $ticket): JsonResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $ticket);

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
        \Illuminate\Support\Facades\Gate::authorize('update', $ticket);

        $data = $request->validate([
            'subject'      => 'sometimes|string|max:200',
            'description'  => 'sometimes|string',
            'status'       => 'sometimes|in:open,pending,resolved,closed',
            'priority'     => 'sometimes|in:low,medium,high,urgent',
            'assigned_to'  => 'sometimes|nullable|exists:users,id',
            'tags'         => 'sometimes|nullable|array',
            'tags.*'       => 'string',
        ]);

        $old = $ticket->only(['status', 'priority', 'assigned_to', 'tags']);
        $ticket->update($data);

        if (isset($data['status']) && $data['status'] !== $old['status']) {
            $this->logActivity($ticket, $request->user()->id, 'status_changed', [
                'from' => $old['status'],
                'to'   => $data['status'],
            ]);

            if (in_array($data['status'], ['resolved', 'closed'])) {
                $ticket->resolved_at = now();
            } else {
                $ticket->resolved_at = null;
            }
            $ticket->save();
        }

        if (array_key_exists('assigned_to', $data) && $data['assigned_to'] !== $old['assigned_to']) {
            $this->logActivity($ticket, $request->user()->id, 'assigned', [
                'to' => $data['assigned_to'],
            ]);

            if ($data['assigned_to']) {
                \App\Models\Notification::create([
                    'organization_id' => $ticket->organization_id,
                    'user_id'         => $data['assigned_to'],
                    'ticket_id'       => $ticket->id,
                    'type'            => 'assigned',
                    'title'           => 'Ticket Assigned to You',
                    'message'         => 'Ticket #' . $ticket->id . ' has been assigned to you by ' . $request->user()->name . '.',
                ]);
            }
        }

        if (array_key_exists('tags', $data) && $data['tags'] !== $old['tags']) {
            $this->logActivity($ticket, $request->user()->id, 'tags_changed', [
                'from' => $old['tags'] ?? [],
                'to'   => $data['tags'] ?? [],
            ]);
        }

        return response()->json(
            $ticket->load(['assignee:id,name,email', 'requester:id,name,email'])
        );
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('delete', $ticket);
        $ticket->delete();
        return response()->json(['message' => 'Ticket deleted.']);
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
