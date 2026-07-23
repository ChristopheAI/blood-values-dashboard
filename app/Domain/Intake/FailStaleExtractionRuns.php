<?php

namespace App\Domain\Intake;

use App\Models\ExtractionRun;
use Illuminate\Support\Carbon;

class FailStaleExtractionRuns
{
    /**
     * A synchronous extraction resolves to 'done' or 'failed' within the same
     * request (seconds; the pdftotext step is capped at 10s). A run still
     * 'pending' long after it was created means the request or worker died
     * mid-run — an OOM kill, a request timeout, or a deploy restart — so the
     * try/catch in RunBloodTestExtraction never got to record the failure and
     * the run would stay 'pending' forever, leaving the intake UI spinning.
     */
    public const STALE_AFTER_MINUTES = 30;

    /**
     * Mark every extraction run that has been stuck 'pending' past the stale
     * window as 'failed'. Returns the number of runs reaped.
     */
    public function __invoke(?Carbon $now = null): int
    {
        $now ??= Carbon::now();
        $cutoff = $now->copy()->subMinutes(self::STALE_AFTER_MINUTES);

        return ExtractionRun::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', $cutoff)
            ->update([
                'status' => 'failed',
                'updated_at' => $now,
            ]);
    }
}
