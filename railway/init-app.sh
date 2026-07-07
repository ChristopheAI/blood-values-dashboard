#!/bin/sh
set -eu

# Railway's pre-deploy step runs in a separate, throwaway container:
# filesystem writes never reach the runtime containers, so artisan cache
# commands (config/event/route/view) are no-ops here — and `optimize:clear`
# would flush the shared database cache store (CACHE_STORE=database) on every
# deploy. Railpack already builds those caches into the image and re-runs
# `optimize` at container start. Only externally visible work belongs here.
php artisan migrate --force
