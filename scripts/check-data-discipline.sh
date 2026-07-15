#!/usr/bin/env sh
# Fail if the tree looks like real personal health data was committed.
# Allowed: synthetic PDFs only under tests/Fixtures/
set -eu

ROOT="$(CDPATH= cd -- "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

fail() {
  echo "DATA-DISCIPLINE FAIL: $*" >&2
  exit 1
}

# 1) No .env or private key material
if [ -f .env ] || [ -f .env.production ] || [ -f .env.local ]; then
  fail ".env-like file present in tree (must stay gitignored / untracked)"
fi

# 2) No PDFs outside tests/Fixtures
pdfs=$(find . -type f \( -iname '*.pdf' \) \
  ! -path './.git/*' \
  ! -path './vendor/*' \
  ! -path './node_modules/*' \
  ! -path './tests/Fixtures/*' 2>/dev/null || true)
if [ -n "$pdfs" ]; then
  echo "$pdfs" >&2
  fail "PDF outside tests/Fixtures/ — only synthetic fixtures allowed"
fi

# 3) Fixtures must stay tiny (synthetic). Real lab PDFs are usually larger.
if [ -d tests/Fixtures ]; then
  find tests/Fixtures -type f -iname '*.pdf' | while read -r f; do
    # size in bytes
    sz=$(wc -c < "$f" | tr -d ' ')
    # 50 KiB ceiling for synthetic fixtures
    if [ "$sz" -gt 51200 ]; then
      fail "Fixture PDF too large ($sz bytes): $f — real labs must not be committed"
    fi
  done
fi

# 4) Block common secret file names
for f in id_rsa id_ed25519 .npmrc.auth credentials.json service-account.json; do
  if find . -name "$f" ! -path './.git/*' ! -path './vendor/*' 2>/dev/null | grep -q .; then
    fail "Sensitive filename found: $f"
  fi
done

# 5) Grep tracked-ish content for high-risk patterns (best-effort, no vendor)
if command -v rg >/dev/null 2>&1; then
  if rg -n --hidden \
    -g '!.git/**' -g '!vendor/**' -g '!node_modules/**' -g '!composer.lock' -g '!package-lock.json' -g '!public/build/**' \
    -e 'BEGIN (RSA |OPENSSH |EC )?PRIVATE KEY' \
    -e 'AKIA[0-9A-Z]{16}' \
    -e 'sk_live_[0-9a-zA-Z]{20,}' \
    -e 'ghp_[0-9A-Za-z]{20,}' \
    -e 'postgres(ql)?://[^:]+:[^@]+@' \
    . 2>/dev/null | grep -v 'check-data-discipline' | grep -q .; then
    rg -n --hidden \
      -g '!.git/**' -g '!vendor/**' -g '!node_modules/**' -g '!composer.lock' -g '!package-lock.json' \
      -e 'BEGIN (RSA |OPENSSH |EC )?PRIVATE KEY' \
      -e 'AKIA[0-9A-Z]{16}' \
      -e 'sk_live_[0-9a-zA-Z]{20,}' \
      -e 'ghp_[0-9A-Za-z]{20,}' \
      -e 'postgres(ql)?://[^:]+:[^@]+@' \
      . 2>/dev/null | grep -v 'check-data-discipline' | head -20 >&2 || true
    fail "Possible secret pattern in tree"
  fi
fi

echo "DATA-DISCIPLINE OK"
