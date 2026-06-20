<?php

namespace App\Enums;

enum DataIsolationOperator: string
{
    case EQ = '=';
    case NEQ = '!=';
    case GT = '>';
    case LT = '<';
    case GTE = '>=';
    case LTE = '<=';
    case IN = 'in';
    case NOT_IN = 'not_in';
    case LIKE = 'like';

    public function label(): string
    {
        return match ($this) {
            self::EQ => '等于 (=)',
            self::NEQ => '不等于 (!=)',
            self::GT => '大于 (>)',
            self::LT => '小于 (<)',
            self::GTE => '大于等于 (>=)',
            self::LTE => '小于等于 (<=)',
            self::IN => '包含 (IN)',
            self::NOT_IN => '不包含 (NOT IN)',
            self::LIKE => '模糊匹配 (LIKE)',
        };
    }

    public static function options(): array
    {
        return array_map(fn ($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }

    public function isArrayOperator(): bool
    {
        return in_array($this, [self::IN, self::NOT_IN], true);
    }
}
