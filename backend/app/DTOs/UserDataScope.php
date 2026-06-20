<?php

namespace App\DTOs;

use App\Enums\DataIsolationScope;

class UserDataScope
{
    public function __construct(
        public readonly DataIsolationScope $scope,
        public readonly string $description,
        public readonly array $details = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'scope' => $this->scope->value,
            'scope_label' => $this->scope->label(),
            'description' => $this->description,
            'details' => $this->details,
        ];
    }
}
