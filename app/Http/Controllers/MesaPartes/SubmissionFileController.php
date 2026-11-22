<?php

namespace App\Http\Controllers\MesaPartes;

use App\Http\Controllers\Controller;
use App\Models\Citizen;
use App\Models\SubmissionFile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Files of the expedientes live on the private disk: only their owner and the staff with access to the
 * Mesa de Partes can download them.
 */
class SubmissionFileController extends Controller
{
    public function citizen(Request $request, SubmissionFile $file): StreamedResponse
    {
        $citizen = $request->user('citizen');

        abort_unless($citizen instanceof Citizen && $file->submission->citizen_id === $citizen->id, 404);

        return $this->download($file);
    }

    public function staff(Request $request, SubmissionFile $file): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->can('view', $file->submission), 403);

        return $this->download($file);
    }

    private function download(SubmissionFile $file): StreamedResponse
    {
        abort_unless(Storage::disk(SubmissionFile::DISK)->exists($file->path), 404);

        return Storage::disk(SubmissionFile::DISK)->download($file->path, $file->original_name, [
            'Cache-Control' => 'no-store, private',
        ]);
    }
}
