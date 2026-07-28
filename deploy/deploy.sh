#!/usr/bin/env bash

set -Eeuo pipefail

APP_PATH="${1:-$(pwd)}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
DEPLOY_BRANCH="${DEPLOY_BRANCH:-master}"
BACKUP_DIR="${BACKUP_DIR:-$APP_PATH/storage/backups/deploy}"
LOCK_DIR="$APP_PATH/storage/framework/deploy.lock"
MAINTENANCE_ENABLED=0

cd "$APP_PATH"

if ! mkdir "$LOCK_DIR" 2>/dev/null; then
    echo "Một tiến trình deploy khác đang chạy."
    exit 1
fi

cleanup() {
    local exit_code=$?

    if [[ "$MAINTENANCE_ENABLED" -eq 1 ]]; then
        "$PHP_BIN" artisan up || true
    fi

    rmdir "$LOCK_DIR" 2>/dev/null || true
    exit "$exit_code"
}
trap cleanup EXIT INT TERM

test -f artisan
test -f .env

echo "Đang cập nhật source nhánh $DEPLOY_BRANCH..."
git fetch origin "$DEPLOY_BRANCH"
git checkout "$DEPLOY_BRANCH"
git pull --ff-only origin "$DEPLOY_BRANCH"

echo "Đang cài dependency production..."
"$COMPOSER_BIN" install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

mkdir -p "$BACKUP_DIR"
find "$BACKUP_DIR" -type f -name 'pre-deploy-*.sql' -mtime +14 -delete
BACKUP_FILE="$BACKUP_DIR/pre-deploy-$(date +%Y%m%d-%H%M%S).sql"

echo "Đang sao lưu MySQL trước migration..."
"$PHP_BIN" artisan db:backup "$BACKUP_FILE"

echo "Đang bật maintenance mode..."
"$PHP_BIN" artisan down --retry=60
MAINTENANCE_ENABLED=1

"$PHP_BIN" artisan optimize:clear
"$PHP_BIN" artisan migrate --force --no-interaction
"$PHP_BIN" artisan optimize

"$PHP_BIN" artisan up
MAINTENANCE_ENABLED=0

echo "Deploy hoàn tất."
