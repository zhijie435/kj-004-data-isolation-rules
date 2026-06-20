#!/usr/bin/env bash
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0

pass() {
    echo -e "${GREEN}[PASS]${NC} $1"
    ((PASS_COUNT++))
}

fail() {
    echo -e "${RED}[FAIL]${NC} $1"
    ((FAIL_COUNT++))
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
    ((WARN_COUNT++))
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

section() {
    echo ""
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  $1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
BACKEND_DIR="${SCRIPT_DIR}/backend"

cd "${BACKEND_DIR}"

section "1. 环境检查"

PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null)
if [ -n "$PHP_VERSION" ]; then
    PHP_MAJOR=$(echo "$PHP_VERSION" | cut -d. -f1)
    PHP_MINOR=$(echo "$PHP_VERSION" | cut -d. -f2)
    if [ "$PHP_MAJOR" -ge 8 ] && [ "$PHP_MINOR" -ge 2 ]; then
        pass "PHP 版本: $PHP_VERSION (>= 8.2)"
    else
        fail "PHP 版本过低: $PHP_VERSION (需要 >= 8.2)"
    fi
else
    fail "无法检测 PHP 版本"
fi

REQUIRED_EXTENSIONS=("bcmath" "ctype" "curl" "dom" "fileinfo" "json" "mbstring" "openssl" "pdo" "pdo_mysql" "tokenizer" "xml")
for ext in "${REQUIRED_EXTENSIONS[@]}"; do
    if php -m | grep -q "^${ext}$"; then
        true
    else
        fail "缺少 PHP 扩展: $ext"
    fi
done
EXT_COUNT=${#REQUIRED_EXTENSIONS[@]}
pass "PHP 扩展检查 (${EXT_COUNT} 项)"

if [ -f "vendor/autoload.php" ]; then
    pass "Composer 依赖已安装"
else
    fail "Composer 依赖未安装，请先运行: composer install"
fi

section "2. 配置检查"

APP_KEY=$(php artisan tinker --execute="echo config('app.key');" 2>/dev/null)
if [ -n "$APP_KEY" ] && [ "$APP_KEY" != "" ]; then
    pass "应用密钥 (APP_KEY) 已配置"
else
    fail "应用密钥 (APP_KEY) 未配置，请运行: php artisan key:generate"
fi

if php artisan tinker --execute="DB::connection()->getPdo();" >/dev/null 2>&1; then
    pass "数据库连接正常"
else
    fail "数据库连接失败，请检查 .env 中的数据库配置"
fi

if php artisan tinker --execute="Redis::ping();" >/dev/null 2>&1; then
    pass "Redis 连接正常"
else
    warn "Redis 连接失败，缓存/队列将使用备用驱动"
fi

WRITABLE_DIRS=("storage" "bootstrap/cache")
for dir in "${WRITABLE_DIRS[@]}"; do
    if [ -w "$dir" ]; then
        true
    else
        fail "目录不可写: $dir"
    fi
done
pass "目录权限检查 (${#WRITABLE_DIRS[@]} 项)"

section "3. 数据库检查"

EXPECTED_TABLES=("tenants" "schools" "users" "data_isolation_rules" "courses" "classes" "class_student" "jobs" "job_batches" "failed_jobs")
TABLE_RESULT=$(php artisan tinker --execute="
\$expected = ['tenants','schools','users','data_isolation_rules','courses','classes','class_student','jobs','job_batches','failed_jobs'];
\$tables = DB::select('SHOW TABLES');
\$tableNames = array_map(fn(\$t) => array_values((array)\$t)[0], \$tables);
\$missing = array_diff(\$expected, \$tableNames);
echo empty(\$missing) ? 'OK' : implode(',', \$missing);
" 2>/dev/null)

if [ "$TABLE_RESULT" = "OK" ]; then
    pass "所有预期数据表已创建"
else
    fail "缺少数据表: $TABLE_RESULT，请运行: php artisan migrate"
fi

MIGRATION_STATUS=$(php artisan migrate:status 2>&1 | grep -c "No" || true)
if [ "$MIGRATION_STATUS" -eq 0 ]; then
    pass "所有迁移已执行"
else
    warn "存在待执行的迁移，请运行: php artisan migrate"
fi

section "4. 种子数据检查"

USER_COUNT=$(php artisan tinker --execute="echo App\Models\User::count();" 2>/dev/null)
if [ "${USER_COUNT:-0}" -ge 5 ]; then
    pass "用户数据已存在 ($USER_COUNT 个用户)"
else
    warn "用户数据不足，请运行: php artisan db:seed --class=UserSeeder"
fi

TENANT_COUNT=$(php artisan tinker --execute="echo App\Models\Tenant::count();" 2>/dev/null)
if [ "${TENANT_COUNT:-0}" -ge 2 ]; then
    pass "租户数据已存在 ($TENANT_COUNT 个租户)"
else
    warn "租户数据不足，请运行: php artisan db:seed --class=TenantSeeder"
fi

RULE_COUNT=$(php artisan tinker --execute="echo App\Models\DataIsolationRule::count();" 2>/dev/null)
if [ "${RULE_COUNT:-0}" -ge 3 ]; then
    pass "数据隔离规则已存在 ($RULE_COUNT 条规则)"
else
    warn "隔离规则不足，请运行: php artisan db:seed --class=DataIsolationRuleSeeder"
fi

section "5. 数据隔离功能验收"

info "测试 1: 超级管理员隔离绕过"
TEST1=$(php artisan tinker --execute="
\$superAdmin = App\Models\User::where('email', 'superadmin@example.com')->first();
if (!\$superAdmin) { echo 'NO_USER'; exit; }
\$svc = app(App\Services\DataIsolationService::class)->setUser(\$superAdmin);
echo \$svc->shouldBypassForCurrentUser() ? 'PASS' : 'FAIL';
" 2>/dev/null)
if [ "$TEST1" = "PASS" ]; then pass "超级管理员可绕过数据隔离"; else
    if [ "$TEST1" = "NO_USER" ]; then warn "缺少超级管理员用户"; else fail "超级管理员隔离绕过测试失败"; fi
fi

info "测试 2: 租户管理员不能绕过隔离"
TEST2=$(php artisan tinker --execute="
\$admin = App\Models\User::where('email', 'th_admin@example.com')->first();
if (!\$admin) { echo 'NO_USER'; exit; }
\$svc = app(App\Services\DataIsolationService::class)->setUser(\$admin);
echo !\$svc->shouldBypassForCurrentUser() ? 'PASS' : 'FAIL';
" 2>/dev/null)
if [ "$TEST2" = "PASS" ]; then pass "租户管理员不可绕过数据隔离"; else
    if [ "$TEST2" = "NO_USER" ]; then warn "缺少租户管理员用户"; else fail "租户管理员隔离绕过测试失败"; fi
fi

info "测试 3: 租户数据隔离验证"
TEST3=$(php artisan tinker --execute="
\$admin = App\Models\User::where('email', 'th_admin@example.com')->first();
if (!\$admin) { echo 'NO_USER'; exit; }
\$query = App\Models\Course::query();
app(App\Services\DataIsolationService::class)->setUser(\$admin)->applyIsolationRules(\$query, new App\Models\Course());
\$courses = \$query->withoutGlobalScopes()->get();
\$allSame = \$courses->every(fn(\$c) => \$c->tenant_id === \$admin->tenant_id);
echo (\$allSame ? 'PASS' : 'FAIL') . ':' . \$courses->count();
" 2>/dev/null)
if echo "$TEST3" | grep -q "^PASS"; then
    COUNT=$(echo "$TEST3" | cut -d: -f2)
    pass "租户隔离生效 (可见 $COUNT 条课程记录)"; else
    if echo "$TEST3" | grep -q "NO_USER"; then warn "缺少租户管理员用户"; else fail "租户数据隔离验证失败"; fi
fi

info "测试 4: 教师课程隔离验证"
TEST4=$(php artisan tinker --execute="
\$teacher = App\Models\User::where('email', 'th_teacher1@example.com')->first();
if (!\$teacher) { echo 'NO_USER'; exit; }
\$query = App\Models\Course::query();
app(App\Services\DataIsolationService::class)->setUser(\$teacher)->applyIsolationRules(\$query, new App\Models\Course());
\$courses = \$query->withoutGlobalScopes()->get();
\$allOwn = \$courses->every(fn(\$c) => \$c->teacher_id === \$teacher->id);
echo (\$allOwn ? 'PASS' : 'FAIL') . ':' . \$courses->count();
" 2>/dev/null)
if echo "$TEST4" | grep -q "^PASS"; then
    COUNT=$(echo "$TEST4" | cut -d: -f2)
    pass "教师课程隔离生效 (可见 $COUNT 条课程记录)"; else
    if echo "$TEST4" | grep -q "NO_USER"; then warn "缺少教师用户"; else fail "教师课程隔离验证失败"; fi
fi

info "测试 5: 用户数据范围摘要"
TEST5=$(php artisan tinker --execute="
\$tests = [
    ['superadmin@example.com', 'global'],
    ['th_teacher1@example.com', 'teacher'],
    ['th_student1@example.com', 'student'],
];
\$allPass = true;
\$missing = false;
foreach (\$tests as [\$email, \$expected]) {
    \$user = App\Models\User::where('email', \$email)->first();
    if (!\$user) { \$missing = true; continue; }
    \$scope = app(App\Services\DataIsolationService::class)->setUser(\$user)->getUserDataScopeSummary()['scope'];
    if (\$scope !== \$expected) { \$allPass = false; }
}
echo \$missing ? 'MISSING' : (\$allPass ? 'PASS' : 'FAIL');
" 2>/dev/null)
if [ "$TEST5" = "PASS" ]; then pass "用户数据范围摘要正确"; else
    if [ "$TEST5" = "MISSING" ]; then warn "缺少测试用户数据"; else fail "用户数据范围摘要验证失败"; fi
fi

section "6. 队列功能验收"

info "测试 1: 刷新隔离规则缓存任务"
if php artisan tinker --execute="\App\Jobs\RefreshDataIsolationCache::dispatchSync(); echo 'OK';" >/dev/null 2>&1; then
    pass "RefreshDataIsolationCache 任务执行成功"
else
    fail "RefreshDataIsolationCache 任务执行失败"
fi

info "测试 2: 验证隔离规则任务"
TEST_JOB2=$(php artisan tinker --execute="
\$result = \App\Jobs\ValidateDataIsolationRules::dispatchSync();
echo \$result['total_rules'] > 0 ? 'PASS:'.\$result['total_rules'].':'.\$result['valid_rules'] : 'FAIL';
" 2>/dev/null)
if echo "$TEST_JOB2" | grep -q "^PASS"; then
    STATS=$(echo "$TEST_JOB2" | cut -d: -f2,3)
    TOTAL=$(echo "$STATS" | cut -d: -f1)
    VALID=$(echo "$STATS" | cut -d: -f2)
    pass "ValidateDataIsolationRules 执行成功 (共 $TOTAL 条，有效 $VALID 条)"
else
    fail "ValidateDataIsolationRules 任务执行失败"
fi

QUEUE_DRIVER=$(php artisan tinker --execute="echo config('queue.default');" 2>/dev/null)
info "队列驱动配置为: $QUEUE_DRIVER"

section "7. API 接口验收（可选）"

info "启动临时 API 服务..."
php artisan serve --host=127.0.0.1 --port=18080 >/dev/null 2>&1 &
SERVE_PID=$!
sleep 3

API_TESTS_PASS=0
API_TESTS_FAIL=0

if curl -s http://127.0.0.1:18080/up >/dev/null 2>&1; then
    pass "API 健康检查通过"
    ((API_TESTS_PASS++))
else
    fail "API 健康检查失败"
    ((API_TESTS_FAIL++))
fi

kill $SERVE_PID 2>/dev/null || true
wait $SERVE_PID 2>/dev/null || true

if [ "$API_TESTS_FAIL" -eq 0 ] && [ "$API_TESTS_PASS" -gt 0 ]; then
    info "API 基本服务正常 (通过 $API_TESTS_PASS 项)"
else
    warn "API 服务存在异常"
fi

section "8. 单元测试验收"

if [ -f "vendor/bin/phpunit" ] || composer show phpunit/phpunit >/dev/null 2>&1; then
    info "运行数据隔离单元测试..."
    if php artisan test --filter=DataIsolationServiceTest --no-ansi 2>&1 | tail -5; then
        TEST_EXIT=${PIPESTATUS[0]:-$?}
        if [ "${TEST_EXIT:-0}" -eq 0 ]; then
            pass "数据隔离单元测试全部通过"
        else
            fail "数据隔离单元测试存在失败"
        fi
    fi
else
    warn "PHPUnit 测试框架未安装（仅开发环境需要）"
fi

section "验收报告汇总"

echo ""
echo -e "${GREEN}通过: $PASS_COUNT${NC}"
echo -e "${YELLOW}警告: $WARN_COUNT${NC}"
echo -e "${RED}失败: $FAIL_COUNT${NC}"
echo ""

if [ "$FAIL_COUNT" -eq 0 ] && [ "$WARN_COUNT" -eq 0 ]; then
    echo -e "${GREEN}============================================${NC}"
    echo -e "${GREEN}  全部验收项通过！系统部署完成 ✓${NC}"
    echo -e "${GREEN}============================================${NC}"
elif [ "$FAIL_COUNT" -eq 0 ]; then
    echo -e "${YELLOW}============================================${NC}"
    echo -e "${YELLOW}  验收通过，但有 $WARN_COUNT 个警告需要关注${NC}"
    echo -e "${YELLOW}============================================${NC}"
else
    echo -e "${RED}============================================${NC}"
    echo -e "${RED}  存在 $FAIL_COUNT 个失败项，请检查并修复${NC}"
    echo -e "${RED}============================================${NC}"
    exit 1
fi

echo ""
info "参考文档: ${SCRIPT_DIR}/DEPLOYMENT.md"
