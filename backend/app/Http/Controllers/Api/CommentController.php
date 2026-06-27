<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, Ticket $ticket): JsonResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('view', $ticket);
        return response()->json(
            $ticket->comments()->with('user:id,name')->latest()->get()
        );
    }

    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $ticket);

        $data = $request->validate([
            'body' => 'required|string',
            'type' => 'in:note,reply',
        ]);

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'type'    => $data['type'] ?? 'reply',
            'body'    => $data['body'],
        ]);

        // SLA Response Trigger: if it's a public reply from agent/admin, mark responded_at
        if ($comment->type === 'reply' && in_array($request->user()->role, ['admin', 'agent'])) {
            if (!$ticket->responded_at) {
                $ticket->responded_at = now();
                $ticket->save();
            }
        }

        // Activity Log for comments/notes
        \App\Models\ActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $request->user()->id,
            'action'    => $comment->type === 'note' ? 'note_added' : 'reply_added',
            'meta'      => ['comment_id' => $comment->id],
        ]);

        // Notifications dispatching
        if ($comment->type === 'reply') {
            if (in_array($request->user()->role, ['admin', 'agent'])) {
                // Agent/admin public reply -> Notify customer (requester)
                if ($ticket->requester_id && $ticket->requester_id !== $request->user()->id) {
                    \App\Models\Notification::create([
                        'organization_id' => $ticket->organization_id,
                        'user_id'         => $ticket->requester_id,
                        'ticket_id'       => $ticket->id,
                        'type'            => 'replied',
                        'title'           => 'New Reply on Your Ticket',
                        'message'         => $request->user()->name . ' replied to Ticket #' . $ticket->id . '.',
                    ]);
                }
            } else {
                // Customer reply -> Notify assignee or all agents
                if ($ticket->assigned_to) {
                    if ($ticket->assigned_to !== $request->user()->id) {
                        \App\Models\Notification::create([
                            'organization_id' => $ticket->organization_id,
                            'user_id'         => $ticket->assigned_to,
                            'ticket_id'       => $ticket->id,
                            'type'            => 'replied',
                            'title'           => 'Customer Replied to Ticket',
                            'message'         => $request->user()->name . ' replied to Ticket #' . $ticket->id . '.',
                        ]);
                    }
                } else {
                    $agents = \App\Models\User::where('organization_id', $ticket->organization_id)
                        ->whereIn('role', ['admin', 'agent'])
                        ->get();
                    foreach ($agents as $agent) {
                        \App\Models\Notification::create([
                            'organization_id' => $ticket->organization_id,
                            'user_id'         => $agent->id,
                            'ticket_id'       => $ticket->id,
                            'type'            => 'replied',
                            'title'           => 'New Reply on Unassigned Ticket',
                            'message'         => $request->user()->name . ' replied to Ticket #' . $ticket->id . '.',
                        ]);
                    }
                }
            }
        } elseif ($comment->type === 'note') {
            // Internal note -> Notify other agents/admins
            $agents = \App\Models\User::where('organization_id', $ticket->organization_id)
                ->whereIn('role', ['admin', 'agent'])
                ->where('id', '!=', $request->user()->id)
                ->get();
            foreach ($agents as $agent) {
                \App\Models\Notification::create([
                    'organization_id' => $ticket->organization_id,
                    'user_id'         => $agent->id,
                    'ticket_id'       => $ticket->id,
                    'type'            => 'replied',
                    'title'           => 'New Internal Note',
                    'message'         => $request->user()->name . ' added an internal note to Ticket #' . $ticket->id . '.',
                ]);
            }
        }

        return response()->json($comment->load('user:id,name'), 201);
    }
}
