<?php

namespace App\Services;

use App\Models\DataIsolationRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DataIsolationService
{
    protected ?User $user = null;

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function shouldBypassForCurrentUser(): bool
    {
        if (!$this->user) {
            return false;
        }

        return $this->user->tenant_id === null && $this->user->type === 'admin';
    }

    public function applyIsolationRules(Builder $query, Model $model): void
    {
        if ($this->shouldBypassForCurrentUser()) {
            return;
        }

        if (!$this->user) {
            $query->whereRaw('1 = 0');
            return;
        }

        $rules = DataIsolationRule::where('is_active', true)
            ->where('model', get_class($model))
            ->orderBy('priority', 'asc')
            ->get();

        foreach ($rules as $rule) {
            $this->applyRule($query, $rule);
        }

        if ($this->user->tenant_id) {
            $query->where('tenant_id', $this->user->tenant_id);
        }

        if ($this->user->type === 'teacher' && $model->getTable() === 'courses') {
            $query->where('teacher_id', $this->user->id);
        }

        if ($this->user->type === 'student' && $model->getTable() === 'courses') {
            $query->whereHas('classes', function ($q) {
                $q->where('student_id', $this->user->id)
                    ->where('status', 'enrolled');
            });
        }
    }

    protected function applyRule(Builder $query, DataIsolationRule $rule): void
    {
        if ($rule->condition_expression) {
            $this->applyConditionExpression($query, $rule);
            return;
        }

        if (!$rule->field || !$rule->operator) {
            return;
        }

        $value = $this->resolveValue($rule);
        if ($value === null) {
            return;
        }

        switch ($rule->operator) {
            case '=':
                $query->where($rule->field, '=', $value);
                break;
            case '!=':
                $query->where($rule->field, '!=', $value);
                break;
            case '>':
                $query->where($rule->field, '>', $value);
                break;
            case '<':
                $query->where($rule->field, '<', $value);
                break;
            case '>=':
                $query->where($rule->field, '>=', $value);
                break;
            case '<=':
                $query->where($rule->field, '<=', $value);
                break;
            case 'in':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereIn($rule->field, $values);
                break;
            case 'not_in':
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereNotIn($rule->field, $values);
                break;
            case 'like':
                $query->where($rule->field, 'like', $value);
                break;
        }
    }

    protected function applyConditionExpression(Builder $query, DataIsolationRule $rule): void
    {
        $expression = $rule->condition_expression;
        $bindings = [];

        $placeholders = [
            '{user_id}' => $this->user->id ?? null,
            '{tenant_id}' => $this->user->tenant_id ?? null,
            '{user_type}' => $this->user->type ?? null,
        ];

        $params = $rule->params ?? [];
        foreach ($params as $key => $value) {
            $placeholders['{' . $key . '}'] = $value;
        }

        foreach ($placeholders as $placeholder => $replacement) {
            if (strpos($expression, $placeholder) !== false) {
                $expression = str_replace($placeholder, '?', $expression);
                $bindings[] = $replacement;
            }
        }

        if (!empty($expression)) {
            $query->whereRaw($expression, $bindings);
        }
    }

    protected function resolveValue(DataIsolationRule $rule): mixed
    {
        $value = $rule->value;

        if (str_starts_with($value, '{{') && str_ends_with($value, '}}')) {
            $key = trim($value, '{{}} ');
            return $this->user->$key ?? null;
        }

        return $value;
    }

    public function checkDataAccess(Model $model): bool
    {
        if ($this->shouldBypassForCurrentUser()) {
            return true;
        }

        if (!$this->user) {
            return false;
        }

        if ($this->user->tenant_id && $model->tenant_id !== $this->user->tenant_id) {
            return false;
        }

        return true;
    }

    public function getUserDataScopeSummary(): array
    {
        if (!$this->user) {
            return ['scope' => 'none', 'description' => '未登录用户'];
        }

        if ($this->shouldBypassForCurrentUser()) {
            return ['scope' => 'global', 'description' => '全局访问（超级管理员）'];
        }

        if ($this->user->type === 'teacher') {
            return ['scope' => 'teacher', 'description' => '仅可访问自己教授的课程'];
        }

        if ($this->user->type === 'student') {
            return ['scope' => 'student', 'description' => '仅可访问已报名的课程'];
        }

        return ['scope' => 'tenant', 'description' => '租户内访问'];
    }
}
