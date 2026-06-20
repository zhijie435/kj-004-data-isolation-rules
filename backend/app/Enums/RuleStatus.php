<?php

namespace App\Enums;

enum RuleStatus: string
{
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';

    public function label(): string
    {
        return match ($this) {
            self::ENABLED => '已启用',
            self::DISABLED => '已禁用',
        };
    }

    public function isEnabled(): bool
    {
        return $this === self::ENABLED;
    }

    public static function fromBoolean(bool $isActive): self
    {
        return $isActive ? self::ENABLED : self::DISABLED;
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return $this !== $newStatus;
    }
}
