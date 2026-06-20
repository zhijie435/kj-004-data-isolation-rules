<?php

namespace App\DTOs;

class RuleStats
{
    public function __construct(
        public readonly int $total,
        public readonly int $enabled,
        public readonly int $disabled,
        public readonly int $modelCount,
    ) {
    }

    public function toArray(): array
    {
        return [
            'total' => $this->total,
            'enabled' => $this->enabled,
            'disabled' => $this->disabled,
            'modelCount' => $this->modelCount,
        ];
    }
}
