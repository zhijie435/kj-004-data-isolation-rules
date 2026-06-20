<?php

namespace App\Enums;

enum DataIsolationScope: string
{
    case GLOBAL = 'global';
    case TENANT = 'tenant';
    case ROLE = 'role';
    case USER = 'user';

    public function label(): string
    {
        return match ($this) {
            self::GLOBAL => '全局',
            self::TENANT => '租户',
            self::ROLE => '角色',
            self::USER => '用户',
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
