<?php

namespace App\Services\DataIsolation\Strategies;

use App\DTOs\UserDataScope;
use App\Enums\DataIsolationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class GuestIsolationStrategy extends AbstractIsolationStrategy
{
    public function apply(Builder $query, Model $model): void
    {
        $this->applyNoResult($query);
    }

    public function canAccess(Model $model): bool
    {
        return false;
    }

    public function getScopeSummary(): UserDataScope
    {
        return new UserDataScope(
            scope: DataIsolationScope::USER,
            description: '未登录用户，无数据访问权限',
            details: []
        );
    }
}
