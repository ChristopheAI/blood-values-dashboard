<?php

namespace App\Domain\Intake;

use Symfony\Component\Process\Process;
use Throwable;

class ExtractPdfLayoutText
{
    public function __invoke(string $pdfPath): ?string
    {
        if (! is_file($pdfPath)) {
            return null;
        }

        $process = new Process(['pdftotext', '-layout', '-nopgbrk', '-enc', 'UTF-8', $pdfPath, '-']);
        $process->setTimeout(10);

        try {
            $process->run();
        } catch (Throwable) {
            return null;
        }

        if (! $process->isSuccessful()) {
            return null;
        }

        $text = trim($process->getOutput());

        return $text === '' ? null : $text;
    }
}
