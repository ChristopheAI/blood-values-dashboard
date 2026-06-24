#!/bin/sh
set -eu

if ! command -v repowise >/dev/null 2>&1; then
    echo "repowise is not installed."
    echo "Install it outside this script, then rerun:"
    echo "  pip install repowise"
    exit 127
fi

export REPOWISE_TELEMETRY_DISABLED=1
export DO_NOT_TRACK=1
export REPOWISE_EMBEDDER=mock
export REPOWISE_SKIP_EDITOR_SETUP=1

set -- \
    -x storage/ \
    -x .env \
    -x .env.* \
    -x "database/*.sqlite" \
    -x database/dusk.sqlite \
    -x vendor/ \
    -x node_modules/ \
    -x public/build/ \
    -x .codex/ \
    -x .omo/ \
    -x .repowise/

if [ ! -d .repowise ]; then
    repowise init . --index-only --no-codex --no-agents --no-distill-hook --yes "$@"
else
    repowise update . --index-only --no-docs --no-agents "$@"
fi

echo
echo "== Repowise health =="
repowise health

echo
echo "== Repowise change risk =="
if [ -n "${REPOWISE_RISK_RANGE:-}" ]; then
    repowise risk "$REPOWISE_RISK_RANGE"
elif git rev-parse --verify main >/dev/null 2>&1; then
    repowise risk main..HEAD
else
    repowise risk HEAD
fi
