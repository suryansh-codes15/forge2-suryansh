<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('organization_id') && ($orgId = app('organization_id'))) {
            $builder->where($model->getTable() . '.organization_id', $orgId);
        }
    }
}
