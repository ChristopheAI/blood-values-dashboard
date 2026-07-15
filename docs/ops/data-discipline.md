# Data discipline (public repository)

This repo is **public**. Treat every commit as world-readable.

## Hard rules

1. **Never commit real lab PDFs** or photos of reports.  
2. **Never commit** exports, SQL dumps, or screenshots with real biomarker values.  
3. **Never commit** `.env`, keys, tokens, or production credentials.  
4. **Synthetic only** under `tests/Fixtures/` (small PDFs, fake names/values).  
5. Real personal use = **local/private deploy only**; production checklist in `production-checklist.md`.

## Allowed in git

- Application code, tests, ADRs, agent rules  
- Synthetic fixtures in `tests/Fixtures/`  
- `.env.example` / `.env.railway.example` without secrets  

## Not allowed

| Path / type | Why |
|---|---|
| Real `*.pdf` outside fixtures | Personal health data |
| Large PDFs in fixtures | Often real labs by mistake |
| `.env`, `*.sqlite` with data | Secrets / records |
| `storage/app/**` uploads | User documents |

## Automation

```bash
sh scripts/check-data-discipline.sh
```

Also run via `sh scripts/validate.sh` and CI.

## If you almost committed real data

1. Do **not** push.  
2. Remove the file from the commit (`git reset` / restore).  
3. If already pushed: rotate any exposed secrets; for personal PDFs consider history rewrite + treat as incident.  
4. Re-run `check-data-discipline.sh`.

## Medical boundary

This software organizes personal tracking. It is **not** a medical device and must not give diagnosis or treatment advice.
