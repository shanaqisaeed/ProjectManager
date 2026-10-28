<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileOptimizationService
{
    public const MAX_FILE_SIZE_BYTES = 20971520; // 20 MB in bytes

    public const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'webp',
        'mp4', 'webm', 'mov',
        'pdf',
    ];

    public const ALLOWED_MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/webp',
        'video/mp4', 'video/webm', 'video/quicktime',
        'application/pdf',
    ];

    /**
     * Validates file size, extension, and MIME type.
     */
    public function validateFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw ValidationException::withMessages([
                'file' => ['حجم فایل انتخاب شده نباید بیشتر از ۲۰ مگابایت باشد.'],
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'file' => ['فرمت فایل مجاز نیست. فقط تصویر (jpg, png, webp)، ویدیو (mp4, webm, mov) و سند (pdf) مجاز است.'],
            ]);
        }

        $mimeType = strtolower($file->getMimeType() ?? '');
        if (! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'file' => ['نوع فایل (MIME Type) معتبر یا مجاز نمی‌باشد.'],
            ]);
        }
    }

    /**
     * Process, optimize, and store the uploaded file safely.
     * Returns relative storage path.
     */
    public function storeOptimizedFile(UploadedFile $file, string $directory = 'attachments'): string
    {
        $this->validateFile($file);

        $extension = strtolower($file->getClientOriginalExtension());
        $randomName = Str::random(40);
        $mimeType = strtolower($file->getMimeType() ?? '');

        // If file is image, attempt WebP optimization if GD is available
        if (str_starts_with($mimeType, 'image/') && function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
            try {
                $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
                if ($image !== false) {
                    $targetFilename = "{$randomName}.webp";
                    $destinationPath = storage_path("app/public/{$directory}/{$targetFilename}");

                    // Ensure target directory exists
                    if (! file_exists(dirname($destinationPath))) {
                        mkdir(dirname($destinationPath), 0755, true);
                    }

                    // Convert and save as WebP with 80% quality
                    imagewebp($image, $destinationPath, 80);
                    imagedestroy($image);

                    return "{$directory}/{$targetFilename}";
                }
            } catch (\Throwable $e) {
                // Fallback to standard upload if image processing fails
            }
        }

        // Standard secure storage with randomized name
        $storedPath = $file->storeAs($directory, "{$randomName}.{$extension}", 'public');

        return $storedPath;
    }
}
