# 数据隔离规则系统部署文档

## 1. 系统概述

本系统是基于 Laravel 11 + Vue 3 的在线课程教务系统，核心功能为**数据隔离规则管理**，支持多租户、多角色（超级管理员/租户管理员/教师/学生）的精细化数据访问控制。

### 技术栈
- **后端**: PHP 8.2+, Laravel 11, MySQL 5.7+, Redis, Sanctum, spatie/laravel-permission
- **前端**: Vue 3, Vite, Element Plus, Pinia, Vue Router, Axios
- **队列**: Redis / Database
- **缓存**: Redis

---

## 2. 环境要求

### 服务器要求
| 组件 | 最低版本 | 推荐版本 |
|------|----------|----------|
| PHP | 8.2 | 8.3+ |
| MySQL | 5.7 | 8.0+ |
| Redis | 5.0 | 7.0+ |
| Nginx | 1.18 | 1.24+ |
| Node.js | 16.0 | 20 LTS |
| npm | 8.0 | 10+ |
| Composer | 2.0 | 2.6+ |

### PHP 扩展
```
bcmath, ctype, curl, dom, fileinfo, json, mbstring, openssl, pdo, pdo_mysql, tokenizer, xml, redis
```

---

## 3. 环境变量配置

### 3.1 复制环境变量文件

```bash
cd backend
cp .env.example .env
```

### 3.2 核心环境变量说明

#### 应用配置
| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `APP_NAME` | EducationDataIsolation | 应用名称 |
| `APP_ENV` | local | 运行环境: local/production/staging |
| `APP_DEBUG` | true | 调试模式，生产环境必须设为 false |
| `APP_KEY` | - | 应用密钥，通过 `php artisan key:generate` 生成 |
| `APP_TIMEZONE` | Asia/Shanghai | 时区设置 |
| `APP_URL` | http://localhost:8000 | 应用访问地址 |

#### 数据库配置
| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `DB_CONNECTION` | mysql | 数据库驱动 |
| `DB_HOST` | 127.0.0.1 | 数据库地址 |
| `DB_PORT` | 3306 | 数据库端口 |
| `DB_DATABASE` | edu_data_isolation | 数据库名 |
| `DB_USERNAME` | root | 数据库用户名 |
| `DB_PASSWORD` | - | 数据库密码 |
| `DB_PREFIX` | - | 数据表前缀（可选） |

#### Redis 配置
| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `REDIS_CLIENT` | phpredis | Redis 客户端 |
| `REDIS_HOST` | 127.0.0.1 | Redis 地址 |
| `REDIS_PORT` | 6379 | Redis 端口 |
| `REDIS_PASSWORD` | null | Redis 密码 |
| `REDIS_DB` | 0 | Redis 数据库编号 |

#### 队列配置
| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `QUEUE_CONNECTION` | redis | 队列驱动: sync/database/redis |
| `QUEUE_FAILED_DB_CONNECTION` | mysql | 失败任务存储连接 |
| `QUEUE_FAILED_TABLE` | failed_jobs | 失败任务表名 |

#### 缓存配置
| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `CACHE_DRIVER` | redis | 缓存驱动: file/redis/database |

#### 认证与 Sanctum
| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `SANCTUM_STATEFUL_DOMAINS` | localhost:5173,... | 允许的前端域名列表 |
| `SPA_URL` | http://localhost:5173 | SPA 前端地址 |

#### 数据隔离专属配置
| 变量名 | 默认值 | 说明 |
|--------|--------|------|
| `DATA_ISOLATION_ENABLED` | true | 是否启用数据隔离功能 |
| `DATA_ISOLATION_CACHE_TTL` | 3600 | 隔离规则缓存时长（秒） |
| `DATA_ISOLATION_SUPER_ADMIN_TYPES` | admin | 超级管理员用户类型 |
| `DATA_ISOLATION_STRICT_MODE` | false | 严格模式：未匹配规则时拒绝所有访问 |

---

## 4. 后端部署步骤

### 4.1 安装依赖

```bash
cd backend
composer install --optimize-autoloader --no-dev
```

> 开发环境可省略 `--no-dev` 以安装测试依赖

### 4.2 生成应用密钥

```bash
php artisan key:generate --ansi
```

### 4.3 数据库迁移

执行所有数据库迁移创建表结构：

```bash
php artisan migrate --force
```

迁移文件清单（按执行顺序）：
1. `2024_01_01_000001_create_tenants_table` - 租户表
2. `2024_01_01_000002_create_schools_table` - 学校表
3. `2024_01_01_000003_create_users_table` - 用户表
4. `2024_01_01_000004_create_data_isolation_rules_table` - 数据隔离规则表
5. `2024_01_01_000005_create_courses_table` - 课程表
6. `2024_01_01_000006_create_classes_table` - 班级表（含班级-学生关联表）
7. `2024_01_01_000007_create_personal_access_tokens_table` - Sanctum Token 表
8. `2024_01_01_000008_create_jobs_table` - 队列表（jobs/batches/failed_jobs）
9. `2024_01_01_000009_add_missing_fields` - 补充字段迁移（type/enrolled_at/status 等）

### 4.4 数据库种子数据

#### 完整初始化（推荐）
```bash
php artisan db:seed --force
```

#### 按模块单独执行
```bash
# 1. 创建角色和权限
php artisan db:seed --class=RoleAndPermissionSeeder --force

# 2. 创建租户数据
php artisan db:seed --class=TenantSeeder --force

# 3. 创建用户账号
php artisan db:seed --class=UserSeeder --force

# 4. 创建数据隔离规则
php artisan db:seed --class=DataIsolationRuleSeeder --force

# 5. 创建演示数据（课程、班级、选课关系）
php artisan db:seed --class=DemoDataSeeder --force
```

#### 种子数据说明
**角色与权限**:
- `super_admin`: 超级管理员，拥有所有权限
- `tenant_admin`: 租户管理员，拥有规则管理权限
- `teacher`: 教师，仅可查看规则、课程和班级
- `student`: 学生，仅可查看课程和班级

**默认用户账号**:
| 邮箱 | 密码 | 角色 | 租户 |
|------|------|------|------|
| superadmin@example.com | password123 | 超级管理员 | 全局 |
| th_admin@example.com | password123 | 租户管理员 | 清华大学 |
| pku_admin@example.com | password123 | 租户管理员 | 北京大学 |
| th_teacher1@example.com | password123 | 教师 | 清华大学 |
| th_teacher2@example.com | password123 | 教师 | 清华大学 |
| th_student1@example.com | password123 | 学生 | 清华大学 |
| th_student2@example.com | password123 | 学生 | 清华大学 |

**预置数据隔离规则**:
| 规则编码 | 类型 | 模型 | 作用 |
|----------|------|------|------|
| COURSE_TENANT_ISOLATION | tenant | Course | 课程租户级隔离 |
| TEACHER_COURSE_ISOLATION | role | Course | 教师仅可见自己课程 |
| STUDENT_ENROLLED_COURSE | custom | Course | 学生仅可见已报名课程 |
| CLASS_TENANT_ISOLATION | tenant | ClassModel | 班级租户级隔离 |
| USER_TENANT_ISOLATION | tenant | User | 用户租户级隔离 |
| ACTIVE_COURSES_ONLY | field | Course | 仅显示启用课程（默认禁用） |

### 4.5 发布 spatie/permission 配置
```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan permission:cache-reset
```

### 4.6 创建软链接（如使用本地文件存储）
```bash
php artisan storage:link
```

### 4.7 启动队列工作进程

#### 方式一：临时运行（开发/测试）
```bash
php artisan queue:work --queue=default --tries=3 --timeout=60
```

#### 方式二：生产环境 Supervisor 配置
创建 `/etc/supervisor/conf.d/data-isolation-worker.conf`:
```ini
[program:data-isolation-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /path/to/backend/artisan queue:work redis --queue=default --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/worker.log
stopwaitsecs=3600
```

启动 Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start data-isolation-worker:*
```

#### 方式三：队列失败重试
```bash
# 重试所有失败任务
php artisan queue:retry all

# 重试指定ID的任务
php artisan queue:retry 1 2 3

# 查看失败任务
php artisan queue:failed

# 清空失败任务
php artisan queue:flush
```

### 4.8 队列任务说明

#### RefreshDataIsolationCache
- **用途**: 刷新数据隔离规则缓存
- **触发时机**: 规则变更后手动/自动触发
- **手动调度**:
```bash
php artisan tinker
>>> \App\Jobs\RefreshDataIsolationCache::dispatch()
```

#### ValidateDataIsolationRules
- **用途**: 验证所有数据隔离规则的合法性
- **参数**:
  - `tenantId`: 可选，按租户验证
  - `ruleId`: 可选，验证单个规则
- **手动调度**:
```bash
php artisan tinker
>>> \App\Jobs\ValidateDataIsolationRules::dispatch()
```

### 4.9 配置定时任务（可选）

在 `routes/console.php` 中添加：
```php
use Illuminate\Support\Facades\Schedule;

Schedule::job(new \App\Jobs\RefreshDataIsolationCache())->hourly();
Schedule::job(new \App\Jobs\ValidateDataIsolationRules())->dailyAt('02:00');
```

添加到 crontab:
```bash
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 5. 前端部署步骤

### 5.1 安装依赖
```bash
cd frontend
npm install
```

### 5.2 开发环境运行
```bash
npm run dev
```
默认监听 `http://localhost:5173`，已配置 `/api` 代理到后端 `http://localhost:8000`

### 5.3 生产环境构建
```bash
npm run build
```
构建产物在 `dist/` 目录，可通过 Nginx 等部署

### 5.4 Nginx 配置示例
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/frontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    location /api {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location = /up {
        proxy_pass http://127.0.0.1:8000;
    }
}
```

### 5.5 Laravel Octane 配置（可选，生产环境推荐）
```bash
composer require laravel/octane
php artisan octane:install --server=swoole
php artisan octane:start --host=127.0.0.1 --port=8000 --workers=4 --max-requests=1000
```

Supervisor 配置:
```ini
[program:octane-data-isolation]
process_name=%(program_name)s
command=/usr/bin/php /path/to/backend/artisan octane:start --server=swoole --host=127.0.0.1 --port=8000 --workers=4
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/backend/storage/logs/octane.log
```

---

## 6. 验收命令清单

### 6.1 环境与依赖检查

```bash
# 1. PHP 版本检查
php -v

# 2. PHP 扩展检查
php -m | grep -E "bcmath|ctype|curl|dom|fileinfo|json|mbstring|openssl|pdo|pdo_mysql|tokenizer|xml|redis"

# 3. Composer 依赖安装状态
cd backend
composer check-platform-reqs

# 4. Node.js 版本检查
node -v
npm -v

# 5. 前端依赖安装状态
cd frontend
npm list --depth=0
```

### 6.2 配置与环境验证

```bash
cd backend

# 1. 应用密钥检查
php artisan tinker --execute="echo config('app.key') ? 'OK' : 'MISSING';"

# 2. 数据库连接测试
php artisan tinker --execute="echo DB::connection()->getPdo() ? 'DB_OK' : 'DB_FAIL';"

# 3. Redis 连接测试
php artisan tinker --execute="echo Redis::ping() ? 'REDIS_OK' : 'REDIS_FAIL';"

# 4. 目录权限检查
php artisan tinker --execute="
\$dirs = ['storage', 'bootstrap/cache'];
foreach (\$dirs as \$dir) {
    echo is_writable(base_path(\$dir)) ? \"\$dir: OK\n\" : \"\$dir: NOT_WRITABLE\n\";
}
"
```

### 6.3 数据库迁移与数据验证

```bash
# 1. 检查迁移状态
php artisan migrate:status

# 2. 检查数据库表是否完整
php artisan tinker --execute="
\$expected = ['tenants','schools','users','data_isolation_rules','courses','classes','class_student','jobs','job_batches','failed_jobs','roles','permissions','model_has_roles','model_has_permissions','role_has_permissions'];
\$tables = DB::select('SHOW TABLES');
\$tableNames = array_map(fn(\$t) => array_values((array)\$t)[0], \$tables);
foreach (\$expected as \$t) {
    echo in_array(\$t, \$tableNames) ? \"\$t: OK\n\" : \"\$t: MISSING\n\";
}
"

# 3. 检查种子数据 - 用户数
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count() . PHP_EOL;"

# 4. 检查种子数据 - 租户数
php artisan tinker --execute="echo 'Tenants: ' . App\Models\Tenant::count() . PHP_EOL;"

# 5. 检查种子数据 - 课程数
php artisan tinker --execute="echo 'Courses: ' . App\Models\Course::count() . PHP_EOL;"

# 6. 检查种子数据 - 隔离规则数
php artisan tinker --execute="echo 'IsolationRules: ' . App\Models\DataIsolationRule::count() . PHP_EOL;"

# 7. 检查种子数据 - 角色数
php artisan tinker --execute="echo 'Roles: ' . Spatie\Permission\Models\Role::count() . PHP_EOL;"
```

### 6.4 数据隔离功能验收

```bash
cd backend

# 1. 超级管理员隔离绕过测试
php artisan tinker --execute="
\$superAdmin = App\Models\User::where('email', 'superadmin@example.com')->first();
\$svc = app(App\Services\DataIsolationService::class)->setUser(\$superAdmin);
echo 'SuperAdmin bypass: ' . (\$svc->shouldBypassForCurrentUser() ? 'PASS' : 'FAIL') . PHP_EOL;
"

# 2. 租户管理员不能绕过测试
php artisan tinker --execute="
\$thAdmin = App\Models\User::where('email', 'th_admin@example.com')->first();
\$svc = app(App\Services\DataIsolationService::class)->setUser(\$thAdmin);
echo 'TenantAdmin bypass: ' . (!\$svc->shouldBypassForCurrentUser() ? 'PASS' : 'FAIL') . PHP_EOL;
"

# 3. 租户隔离 - 清华用户仅见清华课程
php artisan tinker --execute="
\$thAdmin = App\Models\User::where('email', 'th_admin@example.com')->first();
\$query = App\Models\Course::query();
app(App\Services\DataIsolationService::class)->setUser(\$thAdmin)->applyIsolationRules(\$query, new App\Models\Course());
\$courses = \$query->withoutGlobalScopes()->get();
\$allSameTenant = \$courses->every(fn(\$c) => \$c->tenant_id === \$thAdmin->tenant_id);
echo 'Tenant isolation: ' . (\$allSameTenant ? 'PASS' : 'FAIL') . ' (count=' . \$courses->count() . ')' . PHP_EOL;
"

# 4. 教师课程隔离 - 仅见自己教授课程
php artisan tinker --execute="
\$teacher = App\Models\User::where('email', 'th_teacher1@example.com')->first();
\$query = App\Models\Course::query();
app(App\Services\DataIsolationService::class)->setUser(\$teacher)->applyIsolationRules(\$query, new App\Models\Course());
\$courses = \$query->withoutGlobalScopes()->get();
\$allOwnCourses = \$courses->every(fn(\$c) => \$c->teacher_id === \$teacher->id);
echo 'Teacher isolation: ' . (\$allOwnCourses ? 'PASS' : 'FAIL') . ' (count=' . \$courses->count() . ')' . PHP_EOL;
"

# 5. 学生课程隔离 - 仅见已报名课程
php artisan tinker --execute="
\$student = App\Models\User::where('email', 'th_student1@example.com')->first();
\$query = App\Models\Course::query();
app(App\Services\DataIsolationService::class)->setUser(\$student)->applyIsolationRules(\$query, new App\Models\Course());
\$courses = \$query->withoutGlobalScopes()->get();
\$enrolledCount = DB::table('class_student')->where('student_id', \$student->id)->where('status', 'enrolled')->count();
echo 'Student isolation count match: ' . (\$courses->count() <= \$enrolledCount + 1 ? 'PASS' : 'FAIL') . ' (visible=' . \$courses->count() . ', enrolled=' . \$enrolledCount . ')' . PHP_EOL;
"

# 6. 用户数据范围摘要测试
php artisan tinker --execute="
\$tests = [
    ['superadmin@example.com', 'global'],
    ['th_teacher1@example.com', 'teacher'],
    ['th_student1@example.com', 'student'],
];
foreach (\$tests as [\$email, \$expected]) {
    \$user = App\Models\User::where('email', \$email)->first();
    \$scope = app(App\Services\DataIsolationService::class)->setUser(\$user)->getUserDataScopeSummary()['scope'];
    echo \$email . ' scope=' . \$scope . ': ' . (\$scope === \$expected ? 'PASS' : 'FAIL') . PHP_EOL;
}
"

# 7. 跨租户访问拒绝测试
php artisan tinker --execute="
\$thAdmin = App\Models\User::where('email', 'th_admin@example.com')->first();
\$pkuCourse = App\Models\Course::whereHas('tenant', fn(\$q) => \$q->where('code', 'PKU'))->first();
\$svc = app(App\Services\DataIsolationService::class)->setUser(\$thAdmin);
echo 'Cross-tenant access denied: ' . (!\$svc->checkDataAccess(\$pkuCourse) ? 'PASS' : 'FAIL') . PHP_EOL;
"
```

### 6.5 队列功能验收

```bash
cd backend

# 1. 刷新隔离规则缓存任务
php artisan tinker --execute="
\App\Jobs\RefreshDataIsolationCache::dispatchSync();
echo 'RefreshDataIsolationCache: PASS' . PHP_EOL;
"

# 2. 验证隔离规则任务
php artisan tinker --execute="
\$result = \App\Jobs\ValidateDataIsolationRules::dispatchSync();
echo 'ValidateDataIsolationRules: ' . (\$result['total_rules'] > 0 ? 'PASS' : 'FAIL') . ' (total=' . \$result['total_rules'] . ', valid=' . \$result['valid_rules'] . ', invalid=' . count(\$result['invalid_rules']) . ')' . PHP_EOL;
"

# 3. 队列连接检查
php artisan tinker --execute="echo 'Queue driver: ' . config('queue.default') . PHP_EOL;"
php artisan tinker --execute="
try {
    \Illuminate\Support\Facades\Queue::connection()->size();
    echo 'Queue connection: PASS' . PHP_EOL;
} catch (\Exception \$e) {
    echo 'Queue connection: FAIL - ' . \$e->getMessage() . PHP_EOL;
}
"
```

### 6.6 API 接口验收（使用 curl）

```bash
cd backend
APP_URL=http://localhost:8000

# 1. 启动服务（如未启动）
php artisan serve --host=127.0.0.1 --port=8000 &
sleep 2

# 2. 健康检查
echo "=== Health Check ==="
curl -s http://localhost:8000/up

# 3. 登录获取 Token（超级管理员）
echo -e "\n=== Login SuperAdmin ==="
TOKEN=$(curl -s -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"superadmin@example.com","password":"password123"}' | grep -o '"token":"[^"]*' | cut -d'"' -f4)
echo "Token obtained: " ${TOKEN:0:20}...

# 4. 获取隔离规则列表
echo -e "\n=== Get Isolation Rules ==="
curl -s -X GET "http://localhost:8000/api/v1/data-isolation/rules?per_page=5" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool | head -40

# 5. 获取规则类型列表
echo -e "\n=== Get Rule Types ==="
curl -s -X GET http://localhost:8000/api/v1/data-isolation/rule-types \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool

# 6. 获取模型类列表
echo -e "\n=== Get Model Classes ==="
curl -s -X GET http://localhost:8000/api/v1/data-isolation/model-classes \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool

# 7. 创建新的隔离规则
echo -e "\n=== Create Rule ==="
curl -s -X POST http://localhost:8000/api/v1/data-isolation/rules \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "API测试规则",
    "code": "API_TEST_RULE_001",
    "rule_type": "field",
    "model_class": "App\\\\Models\\\\Course",
    "scope": "global",
    "condition_expression": "status = ?",
    "params": {"status": "1"},
    "is_enabled": false,
    "priority": 100,
    "description": "通过API创建的测试规则"
  }' | python3 -m json.tool

# 8. 测试规则 SQL 预览
echo -e "\n=== Test Rule ==="
curl -s -X POST http://localhost:8000/api/v1/data-isolation/test-rule \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "model_class": "App\\\\Models\\\\Course",
    "condition_expression": "status = ?",
    "params": {"status": "1"}
  }' | python3 -m json.tool

# 9. 获取课程列表（带隔离）
echo -e "\n=== Get Courses (With Isolation) ==="
curl -s -X GET http://localhost:8000/api/v1/courses \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json" | python3 -m json.tool

# 10. 停止服务
kill %1 2>/dev/null || true
```

### 6.7 自动化单元测试

```bash
cd backend

# 运行所有测试
php artisan test

# 仅运行数据隔离相关测试
php artisan test --filter=DataIsolationServiceTest

# 带覆盖率报告（需要 Xdebug 或 PCOV）
php artisan test --coverage --min=80

# 仅打印测试结果摘要
php artisan test --compact
```

### 6.8 前端构建验收

```bash
cd frontend

# 1. 代码风格检查
npm run lint

# 2. 生产环境构建
npm run build

# 3. 验证构建产物
ls -la dist/
echo "index.html exists: $([ -f dist/index.html ] && echo 'PASS' || echo 'FAIL')"
echo "assets directory exists: $([ -d dist/assets ] && echo 'PASS' || echo 'FAIL')"
```

### 6.9 性能与安全检查（生产环境）

```bash
cd backend

# 1. 配置缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 2. 权限缓存重置
php artisan permission:cache-reset

# 3. 生产环境优化
php artisan optimize

# 4. 安全检查
composer audit --no-dev

# 5. 代码风格检查（开发依赖）
./vendor/bin/pint --test

# 6. 清除所有缓存（如需重置）
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
php artisan optimize:clear
```

---

## 7. 故障排查

### 7.1 常见问题

**Q: 数据库迁移报 Syntax error or access violation**
```bash
# 确认 MySQL 版本 >= 5.7
mysql --version

# 检查数据库字符集，推荐 utf8mb4
```

**Q: 队列任务不执行**
```bash
# 检查队列进程
ps aux | grep queue:work

# 检查失败任务
php artisan queue:failed

# 检查队列日志
tail -f storage/logs/laravel.log
```

**Q: 数据隔离不生效**
```bash
# 1. 确认模型已添加 GlobalScope
grep -n "DataIsolationScope" app/Models/Course.php

# 2. 确认规则处于激活状态
php artisan tinker --execute="echo App\Models\DataIsolationRule::where('is_active', true)->count();"

# 3. 清除规则缓存
php artisan tinker --execute="\App\Jobs\RefreshDataIsolationCache::dispatchSync();"
```

**Q: Sanctum 认证失败**
```bash
# 确认环境变量配置
grep SANCTUM .env

# 确认请求头包含 Accept: application/json
# 确认 domain 配置正确
```

### 7.2 日志路径
- Laravel 日志: `backend/storage/logs/laravel.log`
- 队列工作日志: `backend/storage/logs/worker.log`（Supervisor 配置的路径）
- Octane 日志: `backend/storage/logs/octane.log`
- Nginx 访问/错误日志: `/var/log/nginx/`

---

## 8. 版本与变更记录

| 日期 | 版本 | 说明 |
|------|------|------|
| 2024-01-01 | v1.0.0 | 初始版本，基础数据隔离规则系统 |

---

*文档结束*
