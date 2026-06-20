<?php

namespace App\DTOs;

class RuleTestResult
{
    public function __construct(
        public readonly string $sql,
        public readonly array $bindings,
        public readonly int $matchedCount,
        public readonly int $affectedCount,
    ) {
    }

    public function toArray(): array
    {
        return [
            'sql' => $this->sql,
            'bindings' => $this->bindings,
            'matched_count' => $this->matchedCount,
            'affected_count' => $this->affectedCount,
        ];
    }
}
