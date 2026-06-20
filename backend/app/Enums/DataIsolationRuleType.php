<?php

namespace App\Enums;

enum DataIsolationRuleType: string
{
    case TENANT = 'tenant';
    case ROLE = 'role';
    case FIELD = 'field';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::TENANT => '租户隔离',
            self::ROLE => '角色隔离',
            self::FIELD => '字段隔离',
            self::CUSTOM => '自定义隔离',
        };
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
