<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-rar-compressed',
    ];

    protected array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp',
        'pdf', 'doc', 'docx', 'xls', 'xlsx',
        'txt', 'csv', 'zip', 'rar',
    ];

    protected int $maxFileSize = 10 * 1024 * 1024; // 10MB

    protected array $malwareSignatures = [
        '<?php',
        '<?=',
        '<%',
        'eval(',
        'exec(',
        'shell_exec(',
        'system(',
        'passthru(',
        'proc_open(',
        'popen(',
        'curl_exec(',
        'curl_multi_exec(',
        'base64_decode(',
        'base64_encode(',
        'gzuncompress(',
        'gzdeflate(',
        'str_rot13(',
        'assert(',
        'pcntl_exec(',
        '`',
        'chmod(',
        'chown(',
        'file_get_contents(',
        'file_put_contents(',
        'fopen(',
        'fwrite(',
        'include(',
        'require(',
        'include_once(',
        'require_once(',
    ];

    public function validate(UploadedFile $file): void
    {
        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException('File size exceeds maximum allowed size of 10MB.');
        }

        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($mimeType, $this->allowedMimeTypes)) {
            throw new \InvalidArgumentException('File type not allowed.');
        }

        if (! in_array($extension, $this->allowedExtensions)) {
            throw new \InvalidArgumentException('File extension not allowed.');
        }
    }

    public function scanForMalware(UploadedFile $file): bool
    {
        $mimeType = $file->getMimeType();

        if (str_starts_with($mimeType, 'image/')) {
            return false;
        }

        $content = file_get_contents($file->getRealPath());

        foreach ($this->malwareSignatures as $signature) {
            if (stripos($content, $signature) !== false) {
                return true;
            }
        }

        return false;
    }

    public function upload(UploadedFile $file, string $directory, ?string $disk = null): string
    {
        $this->validate($file);

        if ($this->scanForMalware($file)) {
            throw new \InvalidArgumentException('File appears to contain malicious content.');
        }

        $disk = $disk ?: config('filesystems.default');
        $path = $file->store($directory, $disk);

        return Storage::disk($disk)->url($path);
    }

    public function delete(string $path, ?string $disk = null): void
    {
        $disk = $disk ?: config('filesystems.default');
        Storage::disk($disk)->delete($path);
    }
}
