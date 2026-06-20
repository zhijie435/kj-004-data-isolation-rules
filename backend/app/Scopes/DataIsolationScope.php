<?php

namespace App\Scopes;

use App\Services\DataIsolationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class DataIsolationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();

        if (!$user) {
            return;
        }

        $isolationService = app(DataIsolationService::class);
        $isolationService->setUser($user);
        $isolationService->applyIsolationRules($builder, $model);
    }
}
