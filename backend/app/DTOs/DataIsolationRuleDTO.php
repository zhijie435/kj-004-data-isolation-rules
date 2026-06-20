<?php

namespace App\DTOs;

use App\Enums\DataIsolationRuleType;
use App\Enums\DataIsolationScope;
use App\Enums\RuleStatus;

class DataIsolationRuleDTO
{
    public function __construct(
        public readonly ?int $id,
        public readonly string $name,
        public readonly string $code,
        public readonly DataIsolationRuleType $ruleType,
        public readonly string $modelClass,
        public readonly DataIsolationScope $scope,
        public readonly ?string $role,
        public readonly ?string $field,
        public readonly ?string $operator,
        public readonly ?string $value,
        public readonly ?string $conditionExpression,
        public readonly ?array $params,
        public readonly ?array $fieldMapping,
        public readonly RuleStatus $status,
        public readonly int $priority,
        public readonly ?string $description,
        public readonly mixed $createdAt,
        public readonly mixed $updatedAt,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $isEnabled = $data['is_enabled'] ?? $data['is_active'] ?? true;

        return new self(
            id: $data['id'] ?? null,
            name: $data['name'],
            code: $data['code'],
            ruleType: DataIsolationRuleType::from($data['rule_type'] ?? $data['type']),
            modelClass: $data['model_class'] ?? $data['model'],
            scope: DataIsolationScope::from($data['scope']),
            role: $data['role'] ?? null,
            field: $data['field'] ?? null,
            operator: $data['operator'] ?? null,
            value: $data['value'] ?? null,
            conditionExpression: $data['condition_expression'] ?? null,
            params: $data['params'] ?? null,
            fieldMapping: $data['field_mapping'] ?? null,
            status: $isEnabled ? RuleStatus::ENABLED : RuleStatus::DISABLED,
            priority: $data['priority'] ?? 0,
            description: $data['description'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'rule_type' => $this->ruleType->value,
            'rule_type_label' => $this->ruleType->label(),
            'model_class' => $this->modelClass,
            'scope' => $this->scope->value,
            'scope_label' => $this->scope->label(),
            'role' => $this->role,
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
            'condition_expression' => $this->conditionExpression,
            'params' => $this->params,
            'field_mapping' => $this->fieldMapping,
            'is_enabled' => $this->status->isEnabled(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }

    public function toModelAttributes(): array
    {
        return [
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->ruleType->value,
            'model' => $this->modelClass,
            'scope' => $this->scope->value,
            'role' => $this->role,
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
            'condition_expression' => $this->conditionExpression,
            'params' => $this->params,
            'field_mapping' => $this->fieldMapping,
            'is_active' => $this->status->isEnabled(),
            'priority' => $this->priority,
            'description' => $this->description,
        ];
    }
}
