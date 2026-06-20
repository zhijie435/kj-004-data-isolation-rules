<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DataIsolationRule;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DataIsolationRuleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DataIsolationRule::query();

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('rule_type')) {
            $query->where('type', $request->input('rule_type'));
        }

        if ($request->filled('model_class')) {
            $query->where('model', $request->input('model_class'));
        }

        if ($request->has('is_enabled') && $request->input('is_enabled') !== '') {
            $query->where('is_active', (bool) $request->input('is_enabled'));
        }

        $perPage = $request->input('per_page', 15);
        $page = $request->input('page', 1);

        $rules = $query->orderBy('priority', 'asc')->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $transformed = $rules->getCollection()->map(function ($rule) {
            return $this->transformRule($rule);
        });

        $statsQuery = DataIsolationRule::query();
        $enabledCount = $statsQuery->clone()->where('is_active', true)->count();
        $disabledCount = $statsQuery->clone()->where('is_active', false)->count();
        $totalCount = $statsQuery->clone()->count();
        $modelCount = $statsQuery->clone()->distinct('model')->count('model');

        return response()->json([
            'success' => true,
            'data' => [
                'data' => $transformed,
                'total' => $rules->total(),
                'current_page' => $rules->currentPage(),
                'per_page' => $rules->perPage(),
                'last_page' => $rules->lastPage(),
                'stats' => [
                    'total' => $totalCount,
                    'enabled' => $enabledCount,
                    'disabled' => $disabledCount,
                    'modelCount' => $modelCount,
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:100|unique:data_isolation_rules,code',
            'rule_type' => 'required|in:tenant,role,field,custom',
            'model_class' => 'required|string|max:200',
            'scope' => 'required|in:global,tenant,role,user',
            'condition_expression' => 'required|string',
            'params' => 'nullable|array',
            'field_mapping' => 'nullable|array',
            'is_enabled' => 'boolean',
            'priority' => 'integer|min:0|max:1000',
            'description' => 'nullable|string|max:500',
        ]);

        $data = [
            'name' => $validated['name'],
            'code' => $validated['code'],
            'type' => $validated['rule_type'],
            'model' => $validated['model_class'],
            'scope' => $validated['scope'],
            'condition_expression' => $validated['condition_expression'],
            'params' => $validated['params'] ?? null,
            'field_mapping' => $validated['field_mapping'] ?? null,
            'is_active' => $validated['is_enabled'] ?? true,
            'priority' => $validated['priority'] ?? 0,
            'description' => $validated['description'] ?? null,
        ];

        if (!empty($validated['field_mapping']) && is_array($validated['field_mapping'])) {
            $keys = array_keys($validated['field_mapping']);
            $firstField = $keys[0] ?? null;
            if ($firstField) {
                $data['field'] = $firstField;
                $data['value'] = $validated['field_mapping'][$firstField];
            }
        }

        $rule = DataIsolationRule::create($data);

        return response()->json([
            'success' => true,
            'data' => $this->transformRule($rule),
            'message' => '创建成功',
        ], 201);
    }

    public function show(DataIsolationRule $rule): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->transformRule($rule),
        ]);
    }

    public function update(Request $request, DataIsolationRule $rule): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:100',
            'code' => 'string|max:100|unique:data_isolation_rules,code,' . $rule->id,
            'rule_type' => 'in:tenant,role,field,custom',
            'model_class' => 'string|max:200',
            'scope' => 'in:global,tenant,role,user',
            'condition_expression' => 'string',
            'params' => 'nullable|array',
            'field_mapping' => 'nullable|array',
            'is_enabled' => 'boolean',
            'priority' => 'integer|min:0|max:1000',
            'description' => 'nullable|string|max:500',
        ]);

        $data = [];

        if (isset($validated['name'])) {
            $data['name'] = $validated['name'];
        }
        if (isset($validated['code'])) {
            $data['code'] = $validated['code'];
        }
        if (isset($validated['rule_type'])) {
            $data['type'] = $validated['rule_type'];
        }
        if (isset($validated['model_class'])) {
            $data['model'] = $validated['model_class'];
        }
        if (isset($validated['scope'])) {
            $data['scope'] = $validated['scope'];
        }
        if (isset($validated['condition_expression'])) {
            $data['condition_expression'] = $validated['condition_expression'];
        }
        if (isset($validated['params'])) {
            $data['params'] = $validated['params'];
        }
        if (isset($validated['field_mapping'])) {
            $data['field_mapping'] = $validated['field_mapping'];
            if (!empty($validated['field_mapping']) && is_array($validated['field_mapping'])) {
                $keys = array_keys($validated['field_mapping']);
                $firstField = $keys[0] ?? null;
                if ($firstField) {
                    $data['field'] = $firstField;
                    $data['value'] = $validated['field_mapping'][$firstField];
                }
            }
        }
        if (isset($validated['is_enabled'])) {
            $data['is_active'] = $validated['is_enabled'];
        }
        if (isset($validated['priority'])) {
            $data['priority'] = $validated['priority'];
        }
        if (isset($validated['description'])) {
            $data['description'] = $validated['description'];
        }

        $rule->update($data);

        return response()->json([
            'success' => true,
            'data' => $this->transformRule($rule),
            'message' => '更新成功',
        ]);
    }

    public function destroy(DataIsolationRule $rule): JsonResponse
    {
        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => '删除成功',
        ]);
    }

    public function toggle(Request $request, DataIsolationRule $rule): JsonResponse
    {
        $validated = $request->validate([
            'is_enabled' => 'required|boolean',
        ]);

        $rule->update([
            'is_active' => $validated['is_enabled'],
        ]);

        return response()->json([
            'success' => true,
            'data' => $this->transformRule($rule),
            'message' => $validated['is_enabled'] ? '已启用' : '已禁用',
        ]);
    }

    public function getRuleTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DataIsolationRule::getTypes(),
        ]);
    }

    public function getModelClasses(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => DataIsolationRule::getModels(),
        ]);
    }

    public function testRule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'model_class' => 'required|string',
            'rule_type' => 'nullable|string',
            'condition_expression' => 'nullable|string',
            'params' => 'nullable|array',
            'field_mapping' => 'nullable|array',
        ]);

        $modelClass = $validated['model_class'];
        if (!class_exists($modelClass)) {
            return response()->json([
                'success' => false,
                'message' => '模型类不存在',
            ], 400);
        }

        $query = $modelClass::query();
        $bindings = [];

        $conditionExpression = $validated['condition_expression'] ?? '';
        $params = $validated['params'] ?? [];
        $fieldMapping = $validated['field_mapping'] ?? [];

        if (!empty($conditionExpression)) {
            $sqlExpression = $conditionExpression;
            foreach ($params as $key => $value) {
                $placeholder = '{' . $key . '}';
                if (strpos($sqlExpression, $placeholder) !== false) {
                    $sqlExpression = str_replace($placeholder, '?', $sqlExpression);
                    $bindings[] = $value;
                }
            }
            if (!empty($sqlExpression)) {
                $query->whereRaw($sqlExpression, $bindings);
            }
        } elseif (!empty($fieldMapping)) {
            foreach ($fieldMapping as $field => $value) {
                if ($value !== '' && $value !== null) {
                    $query->where($field, $value);
                    $bindings[] = $value;
                }
            }
        }

        $sql = $query->toSql();
        $count = $query->count();

        return response()->json([
            'success' => true,
            'data' => [
                'sql' => $sql,
                'bindings' => $bindings,
                'affected_count' => $count,
                'matched_count' => $count,
            ],
        ]);
    }

    protected function transformRule(DataIsolationRule $rule): array
    {
        return [
            'id' => $rule->id,
            'name' => $rule->name,
            'code' => $rule->code,
            'rule_type' => $rule->type,
            'model_class' => $rule->model,
            'scope' => $rule->scope,
            'role' => $rule->role,
            'condition_expression' => $rule->condition_expression,
            'field_mapping' => (object) ($rule->field_mapping ?? []),
            'params' => (object) ($rule->params ?? []),
            'is_enabled' => $rule->is_enabled,
            'priority' => $rule->priority,
            'description' => $rule->description,
            'created_at' => $rule->created_at,
            'updated_at' => $rule->updated_at,
        ];
    }
}
