<?php

namespace App\Services\DataIsolation\Strategies;

use App\DTOs\UserDataScope;
use App\Enums\DataIsolationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TenantIsolationStrategy extends AbstractIsolationStrategy
{
    public function apply(Builder $query, Model $model): void
    {
        if ($this->shouldBypass()) {
            return;
        }

        if (! $this->user) {
            $this->applyNoResult($query);
            return;
        }

        $this->applyTenantFilter($query, $model);
    }

    public function canAccess(Model $model): bool
    {
        if ($this->shouldBypass()) {
            return true;
        }

        if (! $this->user) {
            return false;
        }

        return $this->isSameTenant($model);
    }

    public function getScopeSummary(): UserDataScope
    {
        return new UserDataScope(
            scope: DataIsolationScope::TENANT,
            description: '租户内访问',
            details: ['tenant_id' => $this->user?->tenant_id]
        );
    }
}
