#!/bin/sh
set -eu

echo "== Scaffold files =="
test -f artisan
test -f composer.json
test -f package.json
test -d app
test -d routes
test -d database
echo "ok: Laravel scaffold detected"

echo
echo "== Laravel tests, formatting, and static analysis =="
composer test

echo
echo "== Frontend build =="
npm run build

echo
echo "== Whitespace checks =="
git diff --check
echo "ok: git diff whitespace check passed"

echo
echo "Implementation validation passed."
