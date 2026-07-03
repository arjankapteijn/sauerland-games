# ── Composer deps ────────────────────────────────────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader --no-scripts --ignore-platform-reqs

# ── Frontend assets (Tailwind/Vite) ──────────────────────────────────────
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

# ── Runtime ───────────────────────────────────────────────────────────────
# Eén container, drie processen via supervisord: de webserver (dashboard),
# de Laravel-scheduler (opdracht-release/expiry) en signal:listen (de
# long-poll loop tegen de Signal REST API). Anders dan lovebox/website is
# `read_only` hier niet haalbaar: Laravel heeft storage/, bootstrap/cache/
# en de sqlite-db nodig als schrijfbare paden — die staan in named volumes.
FROM php:8.4-cli-alpine
RUN apk add --no-cache sqlite-dev supervisor \
    && docker-php-ext-install pdo pdo_sqlite \
    && addgroup -g 10001 app && adduser -D -u 10001 -G app app

WORKDIR /var/www/html
COPY --chown=app:app . .
COPY --from=vendor --chown=app:app /app/vendor ./vendor
COPY --from=assets --chown=app:app /app/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache database \
    && touch database/database.sqlite \
    && chown -R app:app storage bootstrap/cache database

RUN mkdir -p /etc/supervisor.d
RUN cat <<'EOF' > /etc/supervisor.d/sauerland-games.ini
[supervisord]
nodaemon=true
user=app
logfile=/dev/null
logfile_maxbytes=0

[program:web]
command=php artisan serve --host=0.0.0.0 --port=8080
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:scheduler]
command=php artisan schedule:work
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:signal-listener]
command=php artisan signal:listen
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

RUN cat <<'EOF' > /entrypoint.sh
#!/bin/sh
set -e
php artisan migrate --force
php artisan db:seed --force
exec "$@"
EOF
RUN chmod +x /entrypoint.sh

VOLUME /var/www/html/storage
VOLUME /var/www/html/database

USER app
ENV APP_ENV=production
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s \
    CMD wget -qO- http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor.d/sauerland-games.ini"]
