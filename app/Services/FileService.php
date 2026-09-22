<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileService
{
    public function __construct(protected FileUploadService $uploader) {}

    public function store(
        UploadedFile $file,
        ?Model $entity = null,
        ?User $uploader = null,
        ?string $disk = null,
        ?string $directory = null
    ): File {
        $this->uploader->validate($file);

        if ($this->uploader->scanForMalware($file)) {
            throw new \InvalidArgumentException('File appears to contain malicious content.');
        }

        $disk = $disk ?: config('filesystems.default', 'local');
        $directory = $directory ?: now()->format('Y/m/d');

        $path = $file->store($directory, $disk);

        return DB::transaction(function () use ($file, $path, $disk, $entity, $uploader) {
            return File::create([
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'extension' => strtolower($file->getClientOriginalExtension()),
                'size' => $file->getSize(),
                'checksum' => $this->checksum($file),
                'uploaded_by' => $uploader?->id,
                'entity_type' => $entity ? $entity->getMorphClass() : null,
                'entity_id' => $entity ? $entity->getKey() : null,
                'is_public' => false,
                'hash' => Str::random(32),
            ]);
        });
    }

    public function addVersion(File $file, UploadedFile $fileUpload, ?string $disk = null): FileVersion
    {
        $this->uploader->validate($fileUpload);

        $disk = $disk ?: config('filesystems.default', 'local');

        return DB::transaction(function () use ($file, $fileUpload, $disk) {
            $path = $fileUpload->store(now()->format('Y/m/d'), $disk);

            $version = $file->versions()->create([
                'disk' => $disk,
                'path' => $path,
                'size' => $fileUpload->getSize(),
                'checksum' => $this->checksum($fileUpload),
            ]);

            $file->update([
                'size' => $fileUpload->getSize(),
                'checksum' => $this->checksum($fileUpload),
            ]);

            return $version;
        });
    }

    public function delete(File $file): void
    {
        DB::transaction(function () use ($file) {
            Storage::disk($file->disk)->delete($file->path);
            $file->versions()->delete();
            $file->delete();
        });
    }

    public function download(File $file): StreamedResponse
    {
        return Storage::disk($file->disk)->response($file->path);
    }

    public function isAccessible(User $user, File $file): bool
    {
        return $user->hasRole('super_admin') || $file->uploaded_by === $user->id || $file->is_public;
    }

    protected function checksum(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }
}
