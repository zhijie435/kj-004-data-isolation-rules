<?php

namespace App\DTOs;

class RuleQueryCriteria
{
    public function __construct(
        public readonly ?string $keyword = null,
        public readonly ?string $ruleType = null,
        public readonly ?string $modelClass = null,
        public readonly ?bool $isEnabled = null,
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly string $sortField = 'priority',
        public readonly string $sortOrder = 'asc',
    ) {
    }

    public static function fromRequest(array $params): self
    {
        return new self(
            keyword: $params['keyword'] ?? null,
            ruleType: $params['rule_type'] ?? null,
            modelClass: $params['model_class'] ?? null,
            isEnabled: isset($params['is_enabled']) && $params['is_enabled'] !== '' ? (bool) $params['is_enabled'] : null,
            page: (int) ($params['page'] ?? 1),
            perPage: (int) ($params['per_page'] ?? 15),
            sortField: $params['sort_field'] ?? 'priority',
            sortOrder: $params['sort_order'] ?? 'asc',
        );
    }
}
