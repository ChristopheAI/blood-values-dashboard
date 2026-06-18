<?php

namespace App\Http\Controllers\BloodTests;

use App\Http\Controllers\Controller;
use App\Models\BloodTestDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadBloodTestDocumentController extends Controller
{
    public function __invoke(BloodTestDocument $bloodTestDocument): StreamedResponse|Response
    {
        abort_unless($bloodTestDocument->bloodTest->user_id === Auth::id(), 403);

        abort_unless(
            Storage::disk($bloodTestDocument->storage_disk)->exists($bloodTestDocument->storage_path),
            404,
        );

        return Storage::disk($bloodTestDocument->storage_disk)->download(
            $bloodTestDocument->storage_path,
            $bloodTestDocument->original_filename,
            ['Content-Type' => $bloodTestDocument->mime_type ?? 'application/pdf'],
        );
    }
}
