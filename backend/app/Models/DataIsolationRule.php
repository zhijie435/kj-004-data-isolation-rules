<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataIsolationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'model',
        'scope',
        'role',
        'field',
        'operator',
        'value',
        'condition_expression',
        'params',
        'field_mapping',
        'is_active',
        'is_enabled',
        'priority',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'params' => 'array',
        'field_mapping' => 'array',
    ];

    protected $appends = ['is_enabled'];

    public function getIsEnabledAttribute(): bool
    {
        return $this->is_active;
    }

    public function setIsEnabledAttribute($value): void
    {
        $this->attributes['is_active'] = (bool) $value;
    }

    const TYPE_TENANT = 'tenant';
    const TYPE_ROLE = 'role';
    const TYPE_FIELD = 'field';
    const TYPE_CUSTOM = 'custom';

    public static function getTypes(): array
    {
        return [
            ['value' => self::TYPE_TENANT, 'label' => '租户隔离'],
            ['value' => self::TYPE_ROLE, 'label' => '角色隔离'],
            ['value' => self::TYPE_FIELD, 'label' => '字段隔离'],
            ['value' => self::TYPE_CUSTOM, 'label' => '自定义隔离'],
        ];
    }

    public static function getOperators(): array
    {
        return [
            ['value' => '=', 'label' => '等于 (=)'],
            ['value' => '!=', 'label' => '不等于 (!=)'],
            ['value' => '>', 'label' => '大于 (>)'],
            ['value' => '<', 'label' => '小于 (<)'],
            ['value' => '>=', 'label' => '大于等于 (>=)'],
            ['value' => '<=', 'label' => '小于等于 (<=)'],
            ['value' => 'in', 'label' => '包含 (IN)'],
            ['value' => 'not_in', 'label' => '不包含 (NOT IN)'],
            ['value' => 'like', 'label' => '模糊匹配 (LIKE)'],
        ];
    }

    public static function getModels(): array
    {
        return [
            ['value' => 'App\\Models\\Course', 'label' => '课程 (Course)'],
            ['value' => 'App\\Models\\ClassModel', 'label' => '班级 (Class)'],
            ['value' => 'App\\Models\\User', 'label' => '用户 (User)'],
        ];
    }
}
