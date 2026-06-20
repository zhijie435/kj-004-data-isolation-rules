<?php

namespace App\Jobs;

use App\Models\DataIsolationRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RefreshDataIsolationCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        $this->onQueue('default');
    }

    public function handle(): void
    {
        $cacheKey = 'data_isolation_rules_all';
        $cacheTtl = (int) env('DATA_ISOLATION_CACHE_TTL', 3600);

        try {
            $rules = DataIsolationRule::where('is_active', true)
                ->orderBy('priority', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->groupBy('model');

            Cache::put($cacheKey, $rules, $cacheTtl);

            foreach ($rules as $modelClass => $modelRules) {
                $modelCacheKey = 'data_isolation_rules_' . md5($modelClass);
                Cache::put($modelCacheKey, $modelRules, $cacheTtl);
            }

            Log::info('数据隔离规则缓存刷新成功', [
                'rule_count' => $rules->count(),
                'model_count' => $rules->keys()->count(),
            ]);
        } catch (\Exception $e) {
            Log::error('数据隔离规则缓存刷新失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
