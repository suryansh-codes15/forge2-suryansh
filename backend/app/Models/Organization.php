<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'plan'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'organization_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'organization_id');
    }

    public function slaPolicies(): HasMany
    {
        return $this->hasMany(SlaPolicy::class, 'organization_id');
    }
}
