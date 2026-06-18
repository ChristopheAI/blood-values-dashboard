<?php

namespace App\Http\Controllers\Privacy;

use App\Domain\Privacy\BuildDataExport;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadDataExportController extends Controller
{
    public function __invoke(BuildDataExport $buildDataExport): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $payload = $buildDataExport($user);
        $filename = 'blood-values-data-export-'.now()->toDateString().'.json';

        return response()->streamDownload(
            function () use ($payload): void {
                echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            },
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }
}
