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
        if ($ticket->organization_id !== $request->user()->organization_id) {
            abort(403);
        }
        return response()->json(
            $ticket->comments()->with('user:id,name')->latest()->get()
        );
    }

    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        if ($ticket->organization_id !== $request->user()->organization_id) {
            abort(403);
        }

        $data = $request->validate([
            'body' => 'required|string',
            'type' => 'in:note,reply',
        ]);

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'type'    => $data['type'] ?? 'reply',
            'body'    => $data['body'],
        ]);

        return response()->json($comment->load('user:id,name'), 201);
    }
}
