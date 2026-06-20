<?php

namespace App\Contracts;

use App\DTOs\RuleQueryCriteria;
use App\DTOs\DataIsolationRuleDTO;
use App\DTOs\RuleStats;
use App\DTOs\RuleTestResult;
use App\Enums\RuleStatus;
use App\Models\DataIsolationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

interface DataIsolationRuleRepositoryInterface
{
    public function query(RuleQueryCriteria $criteria): LengthAwarePaginator;

    public function findById(int $id): ?DataIsolationRule;

    public function findByCode(string $code): ?DataIsolationRule;

    public function create(DataIsolationRuleDTO $dto): DataIsolationRule;

    public function update(DataIsolationRule $rule, DataIsolationRuleDTO $dto): DataIsolationRule;

    public function delete(DataIsolationRule $rule): bool;

    public function changeStatus(DataIsolationRule $rule, RuleStatus $status): DataIsolationRule;

    public function getActiveRulesForModel(string $modelClass): array;

    public function getStats(): RuleStats;

    public function testRule(string $modelClass, ?string $conditionExpression, ?array $params, ?array $fieldMapping): RuleTestResult;
}
