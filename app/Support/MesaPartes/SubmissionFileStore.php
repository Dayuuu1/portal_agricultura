<?php

namespace App\Support\MesaPartes;

use App\Enums\SubmissionFileKind;
use App\Models\Submission;
use App\Models\SubmissionFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Keeps the files of an expediente on the private disk, with the number of pages («folios») and the SHA-256
 * fingerprint printed on the cargo, so the citizen and the entity can prove which file was received.
 */
final class SubmissionFileStore
{
    public static function store(Submission $submission, UploadedFile $file, SubmissionFileKind $kind, ?int $userId = null): SubmissionFile
    {
        // Taken from the content (the validated MIME type), never from the name the browser sent.
        $extension = $file->extension() ?: 'bin';
        $absolutePath = (string) $file->getRealPath();
        $mime = (string) $file->getMimeType();

        // Fingerprint and pages are read from the uploaded file before it is moved to its final place.
        $sha256 = hash_file('sha256', $absolutePath);
        $pages = $mime === 'application/pdf' ? self::pdfPages($absolutePath) : 1;
        $size = (int) $file->getSize();

        return $submission->files()->create([
            'kind' => $kind,
            'path' => $file->storeAs('mesa-de-partes/'.now()->format('Y/m'), Str::uuid().'.'.$extension, SubmissionFile::DISK),
            'original_name' => Str::limit($file->getClientOriginalName(), 180, ''),
            'mime' => $mime,
            'size' => $size,
            'pages' => $pages,
            'sha256' => $sha256,
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Page count read from the PDF structure; at least one page.
     */
    public static function pdfPages(string $path): int
    {
        $content = @file_get_contents($path);

        if ($content === false) {
            return 1;
        }

        return max(1, preg_match_all('/\/Type\s*\/Page(?![a-zA-Z])/', $content));
    }
}
