#!/usr/bin/env bash
set -e

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "${SCRIPT_DIR}")"
BACKEND_DIR="${PROJECT_ROOT}/backend"
FRONTEND_DIR="${PROJECT_ROOT}/frontend"

cd "${BACKEND_DIR}"

echo "=========================================="
echo "  数据隔离规则系统 - 一键部署脚本"
echo "=========================================="
echo ""

STEP=1

step() {
    echo ""
    echo "[$STEP] $1"
    echo "------------------------------------------"
    ((STEP++))
}

step "检查环境依赖"
if ! command -v php &> /dev/null; then
    echo "❌ 未检测到 PHP，请先安装 PHP >= 8.2"
    exit 1
fi
echo "✓ PHP $(php -r 'echo PHP_VERSION;')"

if ! command -v composer &> /dev/null; then
    echo "❌ 未检测到 Composer，请先安装 Composer"
    exit 1
fi
echo "✓ Composer 已安装"

if ! command -v node &> /dev/null; then
    echo "⚠️  未检测到 Node.js（前端构建需要）"
    HAS_NODE=0
else
    echo "✓ Node.js $(node -v)"
    HAS_NODE=1
fi

step "安装后端依赖"
if [ ! -f "vendor/autoload.php" ]; then
    echo "正在运行 composer install ..."
    composer install --optimize-autoloader
    echo "✓ Composer 依赖安装完成"
else
    echo "✓ 依赖已存在，跳过"
fi

step "配置环境变量"
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo "✓ 已复制 .env.example -> .env"
        echo "⚠️  请编辑 .env 配置数据库和 Redis 连接信息"
    else
        echo "⚠️  未找到 .env.example，请手动创建 .env 文件"
    fi
else
    echo "✓ .env 已存在"
fi

step "生成应用密钥"
APP_KEY=$(grep '^APP_KEY=' .env 2>/dev/null | cut -d'=' -f2 || true)
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    php artisan key:generate --ansi
    echo "✓ 应用密钥已生成"
else
    echo "✓ 应用密钥已配置"
fi

step "执行数据库迁移"
echo "请确认已在 .env 中配置正确的数据库连接"
read -p "是否现在执行迁移？(y/N) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan migrate --force
    echo "✓ 数据库迁移完成"
else
    echo "⚠️  跳过迁移，请稍后手动执行: php artisan migrate"
fi

step "执行数据库种子"
read -p "是否导入初始种子数据？(y/N) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan db:seed --force
    echo "✓ 种子数据导入完成"
else
    echo "⚠️  跳过种子，请稍后手动执行: php artisan db:seed"
fi

step "发布权限配置"
if [ ! -f "config/permission.php" ]; then
    read -p "是否发布 spatie/permission 配置？(y/N) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --force
        php artisan permission:cache-reset
        echo "✓ 权限配置发布完成"
    fi
else
    echo "✓ 权限配置已存在"
fi

step "缓存配置（生产环境）"
read -p "当前环境是否为生产环境？(y/N) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan optimize
    echo "✓ 配置缓存完成"
else
    php artisan optimize:clear 2>/dev/null || true
    echo "✓ 已清除所有缓存（开发环境）"
fi

step "安装前端依赖（可选）"
if [ "$HAS_NODE" -eq 1 ] && [ -d "${FRONTEND_DIR}" ]; then
    read -p "是否安装前端依赖并构建？(y/N) " -n 1 -r
    echo ""
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        cd "${FRONTEND_DIR}"
        if [ ! -d "node_modules" ]; then
            npm install
            echo "✓ 前端依赖安装完成"
        fi
        npm run build
        echo "✓ 前端构建完成"
        cd "${BACKEND_DIR}"
    fi
fi

step "启动队列工作进程（可选）"
read -p "是否启动队列工作进程？(需要常驻运行) (y/N) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "启动 queue:work（按 Ctrl+C 停止）..."
    php artisan queue:work --queue=default --tries=3 --timeout=60
fi

echo ""
echo "=========================================="
echo "  部署向导完成！"
echo "=========================================="
echo ""
echo "快速启动命令:"
echo "  后端服务: cd backend && php artisan serve"
echo "  前端开发: cd frontend && npm run dev"
echo "  队列进程: cd backend && php artisan queue:work"
echo ""
echo "系统验收:"
echo "  bash scripts/verify.sh"
echo ""
echo "参考文档:"
echo "  ${PROJECT_ROOT}/DEPLOYMENT.md"
