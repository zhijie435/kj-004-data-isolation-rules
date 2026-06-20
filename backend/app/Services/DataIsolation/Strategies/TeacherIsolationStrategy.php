<?php

namespace App\Services\DataIsolation\Strategies;

use App\DTOs\UserDataScope;
use App\Enums\DataIsolationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TeacherIsolationStrategy extends AbstractIsolationStrategy
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

        if ($model->getTable() === 'courses') {
            $query->where('teacher_id', $this->user->id);
        }
    }

    public function canAccess(Model $model): bool
    {
        if ($this->shouldBypass()) {
            return true;
        }

        if (! $this->user) {
            return false;
        }

        if (! $this->isSameTenant($model)) {
            return false;
        }

        if ($model->getTable() === 'courses' && isset($model->teacher_id)) {
            return $model->teacher_id === $this->user->id;
        }

        return true;
    }

    public function getScopeSummary(): UserDataScope
    {
        return new UserDataScope(
            scope: DataIsolationScope::USER,
            description: '仅可访问自己教授的课程',
            details: ['user_id' => $this->user?->id, 'user_type' => 'teacher']
        );
    }
}
