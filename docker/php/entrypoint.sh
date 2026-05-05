#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
    storage/app/public \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ ! -L public/storage ]; then
    ln -sfn /var/www/html/storage/app/public /var/www/html/public/storage
fi

if [ "${DB_CONNECTION:-}" = "mysql" ] && [ -n "${DB_HOST:-}" ]; then
    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT:-3306}..."
    attempts=0

    until php -r '
        $host = getenv("DB_HOST") ?: "mysql";
        $port = getenv("DB_PORT") ?: "3306";
        $database = getenv("DB_DATABASE") ?: "";
        $username = getenv("DB_USERNAME") ?: "";
        $password = getenv("DB_PASSWORD") ?: "";

        try {
            new PDO("mysql:host={$host};port={$port};dbname={$database}", $username, $password, [
                PDO::ATTR_TIMEOUT => 3,
            ]);
            exit(0);
        } catch (Throwable $exception) {
            fwrite(STDERR, $exception->getMessage().PHP_EOL);
            exit(1);
        }
    '; do
        attempts=$((attempts + 1))

        if [ "$attempts" -ge 30 ]; then
            echo "MySQL did not become ready in time."
            exit 1
        fi

        sleep 2
    done
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force
fi

if [ "${RUN_OPTIMIZE:-false}" = "true" ]; then
    php artisan optimize:clear
    php artisan optimize
fi

exec "$@"
