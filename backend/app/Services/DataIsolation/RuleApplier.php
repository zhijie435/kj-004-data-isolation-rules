<?php

namespace App\Services\DataIsolation;

use App\Enums\DataIsolationOperator;
use App\Exceptions\DataIsolationRuleException;
use App\Models\DataIsolationRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class RuleApplier
{
    public function __construct(protected ?User $user = null)
    {
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function apply(Builder $query, DataIsolationRule $rule): void
    {
        if ($rule->condition_expression) {
            $this->applyConditionExpression($query, $rule);
            return;
        }

        if (! $rule->field || ! $rule->operator) {
            return;
        }

        $value = $this->resolveValue($rule);
        if ($value === null) {
            return;
        }

        $this->applyOperator($query, $rule->field, $rule->operator, $value);
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

        if (! empty($expression)) {
            $query->whereRaw($expression, $bindings);
        }
    }

    protected function applyOperator(Builder $query, string $field, string $operator, mixed $value): void
    {
        try {
            $operatorEnum = DataIsolationOperator::from($operator);
        } catch (\ValueError $e) {
            throw new DataIsolationRuleException(
                "无效的操作符: {$operator}",
                40005,
                ['operator' => $operator]
            );
        }

        switch ($operatorEnum) {
            case DataIsolationOperator::EQ:
                $query->where($field, '=', $value);
                break;
            case DataIsolationOperator::NEQ:
                $query->where($field, '!=', $value);
                break;
            case DataIsolationOperator::GT:
                $query->where($field, '>', $value);
                break;
            case DataIsolationOperator::LT:
                $query->where($field, '<', $value);
                break;
            case DataIsolationOperator::GTE:
                $query->where($field, '>=', $value);
                break;
            case DataIsolationOperator::LTE:
                $query->where($field, '<=', $value);
                break;
            case DataIsolationOperator::IN:
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereIn($field, $values);
                break;
            case DataIsolationOperator::NOT_IN:
                $values = is_array($value) ? $value : explode(',', $value);
                $query->whereNotIn($field, $values);
                break;
            case DataIsolationOperator::LIKE:
                $query->where($field, 'like', $value);
                break;
        }
    }

    protected function resolveValue(DataIsolationRule $rule): mixed
    {
        $value = $rule->value;

        if (is_string($value) && str_starts_with($value, '{{') && str_ends_with($value, '}}')) {
            $key = trim($value, '{{}} ');
            return $this->user?->$key ?? null;
        }

        return $value;
    }
}
