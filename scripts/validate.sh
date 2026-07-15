#!/bin/sh

# Data discipline (public repo: no real health data / secrets)
sh scripts/check-data-discipline.sh

set -eu

DUSK_ENV_CREATED=0
DUSK_PRESERVED_ENV_BACKUP=""

cleanup() {
    if [ -n "${DUSK_SERVER_PID:-}" ] && kill -0 "$DUSK_SERVER_PID" 2>/dev/null; then
        kill "$DUSK_SERVER_PID" 2>/dev/null || true
        wait "$DUSK_SERVER_PID" 2>/dev/null || true
    fi

    if [ "$DUSK_ENV_CREATED" = "1" ]; then
        if [ -f .env.backup ]; then
            mv .env.backup .env
        fi

        if [ -n "$DUSK_PRESERVED_ENV_BACKUP" ] && [ -f "$DUSK_PRESERVED_ENV_BACKUP" ]; then
            mv "$DUSK_PRESERVED_ENV_BACKUP" .env.backup
        fi

        rm -f .env.dusk.local database/dusk.sqlite
    fi
}

trap cleanup EXIT INT TERM

echo "== Scaffold files =="
test -f artisan
test -f composer.json
test -f package.json
test -d app
test -d routes
test -d database
echo "ok: Laravel scaffold detected"

echo
echo "== Control-plane docs =="
test -f docs/adr/0005-use-pdf-first-intake-with-confirmed-values.md
test -f docs/adr/0006-use-exa-and-firecrawl-as-public-research-tools.md
test -f docs/adr/0007-use-staged-laravel-quality-ladder.md
test -f docs/adr/0008-future-ai-agents-must-be-proposal-only.md
test -f docs/adr/0009-use-local-best-effort-pdf-extraction.md
test -f docs/adr/0010-use-layout-aware-positional-text-extraction.md
test -f docs/adr/0012-use-local-repo-intelligence-for-agent-workflows.md
test -f scripts/repowise-local-check.sh
echo "ok: ADR guardrails detected"

echo
echo "== Parser-lab tests =="
python3 -m unittest discover -s tests/python -p 'test_*.py'

echo
echo "== Frontend build =="
npm run build

echo
echo "== Laravel tests, formatting, and static analysis =="
composer test

echo
echo "== Browser smoke =="
mkdir -p database storage/logs
rm -f .env.dusk.local database/dusk.sqlite
touch database/dusk.sqlite
DUSK_ENV_CREATED=1

APP_KEY_VALUE="$(grep '^APP_KEY=' .env | cut -d= -f2-)"
DUSK_DATABASE="$(pwd)/database/dusk.sqlite"
DUSK_APP_URL="http://127.0.0.1:8010"

if [ -f .env.backup ]; then
    DUSK_PRESERVED_ENV_BACKUP=".env.backup.validate.$$"
    mv .env.backup "$DUSK_PRESERVED_ENV_BACKUP"
fi

cat > .env.dusk.local <<EOF
APP_NAME=Laravel
APP_ENV=local
APP_KEY=${APP_KEY_VALUE}
APP_DEBUG=true
APP_URL=${DUSK_APP_URL}
APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US
LOG_CHANNEL=single
DB_CONNECTION=sqlite
DB_DATABASE="${DUSK_DATABASE}"
SESSION_DRIVER=file
CACHE_STORE=array
QUEUE_CONNECTION=sync
MAIL_MAILER=array
BCRYPT_ROUNDS=4
EOF

php artisan dusk:chrome-driver --detect

APP_KEY="$APP_KEY_VALUE" \
APP_ENV=local \
APP_DEBUG=true \
APP_URL="$DUSK_APP_URL" \
DB_CONNECTION=sqlite \
DB_DATABASE="$DUSK_DATABASE" \
SESSION_DRIVER=file \
CACHE_STORE=array \
QUEUE_CONNECTION=sync \
MAIL_MAILER=array \
BCRYPT_ROUNDS=4 \
    php artisan serve --host=127.0.0.1 --port=8010 --no-reload > storage/logs/dusk-server.log 2>&1 &
DUSK_SERVER_PID=$!

tries=0
until curl -fsS "$DUSK_APP_URL" >/dev/null 2>&1; do
    tries=$((tries + 1))

    if [ "$tries" -ge 30 ]; then
        echo "Dusk server did not start on ${DUSK_APP_URL}."
        tail -80 storage/logs/dusk-server.log || true
        exit 1
    fi

    sleep 1
done

if ! php artisan dusk --without-tty; then
    tail -80 storage/logs/dusk-server.log || true
    exit 1
fi

echo
echo "== Whitespace checks =="
git diff --check
echo "ok: git diff whitespace check passed"

echo
echo "Implementation validation passed."
