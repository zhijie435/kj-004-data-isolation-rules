<?php

namespace App\Repositories;

use App\Contracts\DataIsolationRuleRepositoryInterface;
use App\DTOs\DataIsolationRuleDTO;
use App\DTOs\RuleQueryCriteria;
use App\DTOs\RuleStats;
use App\DTOs\RuleTestResult;
use App\Enums\RuleStatus;
use App\Exceptions\DataIsolationRuleException;
use App\Models\DataIsolationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DataIsolationRuleRepository implements DataIsolationRuleRepositoryInterface
{
    protected const CACHE_TTL = 300;

    protected const ACTIVE_RULES_CACHE_KEY = 'data_isolation:active_rules:';

    public function query(RuleQueryCriteria $criteria): LengthAwarePaginator
    {
        $query = DataIsolationRule::query();

        $this->applyFilters($query, $criteria);
        $this->applySorting($query, $criteria);

        return $query->paginate(
            $criteria->perPage,
            ['*'],
            'page',
            $criteria->page
        );
    }

    protected function applyFilters(Builder $query, RuleQueryCriteria $criteria): void
    {
        if ($criteria->keyword) {
            $keyword = $criteria->keyword;
            $query->where(function (Builder $q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        if ($criteria->ruleType) {
            $query->where('type', $criteria->ruleType);
        }

        if ($criteria->modelClass) {
            $query->where('model', $criteria->modelClass);
        }

        if ($criteria->isEnabled !== null) {
            $query->where('is_active', $criteria->isEnabled);
        }
    }

    protected function applySorting(Builder $query, RuleQueryCriteria $criteria): void
    {
        $allowedSorts = ['priority', 'id', 'created_at', 'updated_at'];
        $sortField = in_array($criteria->sortField, $allowedSorts, true)
            ? $criteria->sortField
            : 'priority';
        $sortOrder = strtolower($criteria->sortOrder) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortField, $sortOrder);

        if ($sortField !== 'id') {
            $query->orderBy('id', 'desc');
        }
    }

    public function findById(int $id): ?DataIsolationRule
    {
        return DataIsolationRule::find($id);
    }

    public function findByCode(string $code): ?DataIsolationRule
    {
        return DataIsolationRule::where('code', $code)->first();
    }

    public function create(DataIsolationRuleDTO $dto): DataIsolationRule
    {
        $existing = $this->findByCode($dto->code);
        if ($existing) {
            throw new DataIsolationRuleException(
                "规则编码 [{$dto->code}] 已存在",
                40003,
                ['code' => $dto->code]
            );
        }

        $attributes = $dto->toModelAttributes();

        if (!empty($dto->fieldMapping) && is_array($dto->fieldMapping)) {
            $keys = array_keys($dto->fieldMapping);
            $firstField = $keys[0] ?? null;
            if ($firstField) {
                $attributes['field'] = $firstField;
                $attributes['value'] = $dto->fieldMapping[$firstField];
            }
        }

        $rule = DataIsolationRule::create($attributes);

        $this->clearModelCache($dto->modelClass);

        return $rule;
    }

    public function update(DataIsolationRule $rule, DataIsolationRuleDTO $dto): DataIsolationRule
    {
        $attributes = $dto->toModelAttributes();
        unset($attributes['is_active']);

        if (!empty($dto->fieldMapping) && is_array($dto->fieldMapping)) {
            $keys = array_keys($dto->fieldMapping);
            $firstField = $keys[0] ?? null;
            if ($firstField) {
                $attributes['field'] = $firstField;
                $attributes['value'] = $dto->fieldMapping[$firstField];
            }
        }

        $oldModelClass = $rule->model;
        $rule->update($attributes);

        if ($oldModelClass !== $dto->modelClass) {
            $this->clearModelCache($oldModelClass);
        }
        $this->clearModelCache($dto->modelClass);

        return $rule->fresh();
    }

    public function delete(DataIsolationRule $rule): bool
    {
        $modelClass = $rule->model;
        $result = $rule->delete();

        if ($result) {
            $this->clearModelCache($modelClass);
        }

        return $result;
    }

    public function changeStatus(DataIsolationRule $rule, RuleStatus $status): DataIsolationRule
    {
        $rule->transitionTo($status);
        $rule->save();

        $this->clearModelCache($rule->model);

        return $rule->fresh();
    }

    public function getActiveRulesForModel(string $modelClass): array
    {
        $cacheKey = self::ACTIVE_RULES_CACHE_KEY . md5($modelClass);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($modelClass) {
            return DataIsolationRule::where('is_active', true)
                ->where('model', $modelClass)
                ->orderBy('priority', 'asc')
                ->get()
                ->all();
        });
    }

    public function getStats(): RuleStats
    {
        $query = DataIsolationRule::query();

        return new RuleStats(
            total: (int) (clone $query)->count(),
            enabled: (int) (clone $query)->where('is_active', true)->count(),
            disabled: (int) (clone $query)->where('is_active', false)->count(),
            modelCount: (int) (clone $query)->distinct('model')->count('model'),
        );
    }

    public function testRule(
        string $modelClass,
        ?string $conditionExpression,
        ?array $params,
        ?array $fieldMapping
    ): RuleTestResult {
        if (! class_exists($modelClass)) {
            throw new DataIsolationRuleException(
                "模型类 [{$modelClass}] 不存在",
                40004,
                ['model_class' => $modelClass]
            );
        }

        $query = $modelClass::query();
        $bindings = [];

        if (! empty($conditionExpression)) {
            $sqlExpression = $conditionExpression;
            $params = $params ?? [];
            foreach ($params as $key => $value) {
                $placeholder = '{' . $key . '}';
                if (strpos($sqlExpression, $placeholder) !== false) {
                    $sqlExpression = str_replace($placeholder, '?', $sqlExpression);
                    $bindings[] = $value;
                }
            }
            if (! empty($sqlExpression)) {
                $query->whereRaw($sqlExpression, $bindings);
            }
        } elseif (! empty($fieldMapping)) {
            foreach ($fieldMapping as $field => $value) {
                if ($value !== '' && $value !== null) {
                    $query->where($field, $value);
                    $bindings[] = $value;
                }
            }
        }

        $countQuery = clone $query;
        $count = (int) $countQuery->count();

        return new RuleTestResult(
            sql: $query->toSql(),
            bindings: $bindings,
            matchedCount: $count,
            affectedCount: $count,
        );
    }

    protected function clearModelCache(string $modelClass): void
    {
        Cache::forget(self::ACTIVE_RULES_CACHE_KEY . md5($modelClass));
    }
}
