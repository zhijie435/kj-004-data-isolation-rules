<?php

namespace App\Services\DataIsolation\Strategies;

use App\Contracts\DataIsolationStrategyInterface;
use App\DTOs\UserDataScope;
use App\Enums\DataIsolationScope;
use App\Exceptions\UnauthorizedDataAccessException;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractIsolationStrategy implements DataIsolationStrategyInterface
{
    protected ?User $user = null;

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function shouldBypass(): bool
    {
        if (! $this->user) {
            return false;
        }

        return $this->user->tenant_id === null && $this->user->type === 'admin';
    }

    abstract public function apply(Builder $query, Model $model): void;

    abstract public function canAccess(Model $model): bool;

    public function ensureCanAccess(Model $model): void
    {
        if (! $this->canAccess($model)) {
            throw new UnauthorizedDataAccessException(
                '无权访问该数据',
                40301,
                [
                    'model' => get_class($model),
                    'model_id' => $model->getKey(),
                    'user_id' => $this->user?->id,
                ]
            );
        }
    }

    abstract public function getScopeSummary(): UserDataScope;

    protected function isSameTenant(Model $model): bool
    {
        if (! $this->user || ! $this->user->tenant_id) {
            return true;
        }

        if (! isset($model->tenant_id)) {
            return true;
        }

        return $model->tenant_id === $this->user->tenant_id;
    }

    protected function applyNoResult(Builder $query): void
    {
        $query->whereRaw('1 = 0');
    }

    protected function applyTenantFilter(Builder $query, Model $model): void
    {
        if (! $this->user || ! $this->user->tenant_id) {
            return;
        }

        if (in_array('tenant_id', $model->getFillable(), true)
            || $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), 'tenant_id')) {
            $query->where('tenant_id', $this->user->tenant_id);
        }
    }
}
