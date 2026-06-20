<?php

namespace App\Jobs;

use App\Models\DataIsolationRule;
use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ValidateDataIsolationRules implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?int $tenantId;
    protected ?int $ruleId;

    public function __construct(?int $tenantId = null, ?int $ruleId = null)
    {
        $this->tenantId = $tenantId;
        $this->ruleId = $ruleId;
        $this->onQueue('default');
    }

    public function handle(): array
    {
        $results = [
            'total_rules' => 0,
            'valid_rules' => 0,
            'invalid_rules' => [],
            'warnings' => [],
        ];

        try {
            $query = DataIsolationRule::query();

            if ($this->ruleId) {
                $query->where('id', $this->ruleId);
            }

            $rules = $query->get();
            $results['total_rules'] = $rules->count();

            foreach ($rules as $rule) {
                $validationResult = $this->validateSingleRule($rule);

                if ($validationResult['valid']) {
                    $results['valid_rules']++;
                } else {
                    $results['invalid_rules'][] = [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                        'rule_code' => $rule->code,
                        'errors' => $validationResult['errors'],
                    ];
                }

                if (!empty($validationResult['warnings'])) {
                    $results['warnings'][] = [
                        'rule_id' => $rule->id,
                        'rule_name' => $rule->name,
                        'warnings' => $validationResult['warnings'],
                    ];
                }
            }

            Log::info('数据隔离规则验证完成', $results);
        } catch (\Exception $e) {
            Log::error('数据隔离规则验证异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        return $results;
    }

    protected function validateSingleRule(DataIsolationRule $rule): array
    {
        $errors = [];
        $warnings = [];

        if (!class_exists($rule->model)) {
            $errors[] = "模型类不存在: {$rule->model}";
        }

        $validTypes = ['tenant', 'role', 'field', 'custom'];
        if (!in_array($rule->type, $validTypes)) {
            $errors[] = "无效的规则类型: {$rule->type}";
        }

        $validScopes = ['global', 'tenant', 'role', 'user'];
        if (!in_array($rule->scope, $validScopes)) {
            $errors[] = "无效的规则作用域: {$rule->scope}";
        }

        if (empty($rule->condition_expression) && empty($rule->field)) {
            $errors[] = '规则必须指定条件表达式或字段映射';
        }

        if (!empty($rule->condition_expression) && $this->hasSqlInjectionRisk($rule->condition_expression)) {
            $warnings[] = '条件表达式可能存在SQL注入风险，建议使用参数绑定';
        }

        if ($rule->priority < 0 || $rule->priority > 1000) {
            $warnings[] = '优先级建议设置在 0-1000 范围内';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    protected function hasSqlInjectionRisk(string $expression): bool
    {
        $dangerousPatterns = [
            '/\bDROP\b/i',
            '/\bDELETE\b.*\bFROM\b/i',
            '/\bUPDATE\b.*\bSET\b/i',
            '/\bINSERT\b.*\bINTO\b/i',
            '/\bALTER\b.*\bTABLE\b/i',
            '/\bCREATE\b.*\bTABLE\b/i',
            '/\bTRUNCATE\b/i',
            '/--/',
            '/\/\*/',
            '/\*\//',
            '/;/',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $expression)) {
                return true;
            }
        }

        return false;
    }
}
