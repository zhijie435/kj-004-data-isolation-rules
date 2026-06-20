<?php

namespace App\Services\DataIsolation\Strategies;

use App\DTOs\UserDataScope;
use App\Enums\DataIsolationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GlobalAdminIsolationStrategy extends AbstractIsolationStrategy
{
    public function apply(Builder $query, Model $model): void
    {
        return;
    }

    public function canAccess(Model $model): bool
    {
        return true;
    }

    public function getScopeSummary(): UserDataScope
    {
        return new UserDataScope(
            scope: DataIsolationScope::GLOBAL,
            description: '全局访问（超级管理员）',
            details: ['user_type' => 'admin']
        );
    }
}
