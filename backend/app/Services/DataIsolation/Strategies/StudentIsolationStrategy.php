<?php

namespace App\Services\DataIsolation\Strategies;

use App\DTOs\UserDataScope;
use App\Enums\DataIsolationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentIsolationStrategy extends AbstractIsolationStrategy
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
            $userId = $this->user->id;
            $query->whereHas('classes', function (Builder $q) use ($userId) {
                $q->where('student_id', $userId)
                    ->where('status', 'enrolled');
            });
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

        if ($model->getTable() === 'courses') {
            return $model->classes()
                ->where('student_id', $this->user->id)
                ->where('status', 'enrolled')
                ->exists();
        }

        return true;
    }

    public function getScopeSummary(): UserDataScope
    {
        return new UserDataScope(
            scope: DataIsolationScope::USER,
            description: '仅可访问已报名的课程',
            details: ['user_id' => $this->user?->id, 'user_type' => 'student']
        );
    }
}
