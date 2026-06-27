<?php

namespace App\Models;

use App\Models\Scopes\OrganizationScope;
use App\Models\SlaPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    protected $fillable = [
        'organization_id', 'requester_id', 'assigned_to',
        'subject', 'description', 'status', 'priority', 'tags',
        'response_due_at', 'resolution_due_at', 'responded_at', 'resolved_at',
        'csat_rating',
    ];

    protected $casts = [
        'tags' => 'array',
        'response_due_at'   => 'datetime',
        'resolution_due_at' => 'datetime',
        'responded_at'      => 'datetime',
        'resolved_at'       => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new OrganizationScope());

        static::creating(function (Ticket $ticket) {
            if (!$ticket->organization_id) {
                $ticket->organization_id = app('organization_id') ?? (auth()->check() ? auth()->user()->organization_id : null);
            }
            $ticket->resolveSlaTimes();
        });

        static::updating(function (Ticket $ticket) {
            if ($ticket->isDirty('priority')) {
                $ticket->resolveSlaTimes();
            }
        });
    }

    public function resolveSlaTimes(): void
    {
        $orgId = $this->organization_id ?? app('organization_id') ?? (auth()->check() ? auth()->user()->organization_id : null);
        if (!$orgId) {
            return;
        }

        $policy = SlaPolicy::where('organization_id', $orgId)
            ->where('priority', $this->priority ?? 'medium')
            ->first();

        // Defaults
        $respHours = 8;
        $resHours = 24;

        if ($policy) {
            $respHours = $policy->response_time_hours;
            $resHours = $policy->resolution_time_hours;
        } else {
            switch ($this->priority) {
                case 'urgent':
                    $respHours = 1;
                    $resHours = 4;
                    break;
                case 'high':
                    $respHours = 4;
                    $resHours = 12;
                    break;
                case 'medium':
                    $respHours = 8;
                    $resHours = 24;
                    break;
                case 'low':
                    $respHours = 24;
                    $resHours = 72;
                    break;
            }
        }

        $baseTime = $this->created_at ? \Illuminate\Support\Carbon::parse($this->created_at) : now();

        if (!$this->responded_at) {
            $this->response_due_at = $baseTime->copy()->addHours($respHours);
        }
        if (!$this->resolved_at) {
            $this->resolution_due_at = $baseTime->copy()->addHours($resHours);
        }
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }
}
