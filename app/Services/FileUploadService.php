<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FileUploadService
{
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'image/svg+xml',
    ];

    private const ALLOWED_EXTENSIONS = [
        'jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'
    ];

    private const BLOCKED_EXTENSIONS = [
        'exe', 'sh', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js', 'jse',
        'wsf', 'wsh', 'msh', 'mww', 'php', 'phtml', 'phar', 'phps', 'phpt',
        'sql', 'pl', 'py', 'rb', 'cgi', 'ini', 'jar', 'war', 'ear',
        'html', 'htm', 'xhtml', 'shtml', 'stm', 'asp', 'aspx', 'ascx',
    ];

    private const MAX_FILE_SIZE = 5242880; // 5MB

    public function validateUpload(Request $request, string $fieldName): ?string
    {
        if (!$request->hasFile($fieldName)) {
            return null; // Field optional
        }

        $file = $request->file($fieldName);

        // Check if file is valid
        if (!$file->isValid()) {
            return 'File upload failed';
        }

        // Check file size
        if ($file->getSize() > self::MAX_FILE_SIZE) {
            return 'File size exceeds 5MB limit';
        }

        // Check extension first (block dangerous ones)
        $extension = strtolower($file->getClientOriginalExtension());
        if (in_array($extension, self::BLOCKED_EXTENSIONS)) {
            Log::warning('Blocked dangerous file upload attempt', [
                'extension' => $extension,
                'filename' => $file->getClientOriginalName(),
                'ip' => $request->ip(),
            ]);
            return 'File extension blocked for security reasons';
        }

        if (!in_array($extension, self::ALLOWED_EXTENSIONS)) {
            return 'File extension not allowed';
        }

        // Verify MIME type using finfo (more reliable than getMimeType)
        $realMimeType = $this->getRealMimeTypa($file->getRealPath());
        if (!in_array($realMimeType, self::ALLOWED_MIME_TYPES)) {
            Log::warning('File MIME type mismatch', [
                'client_mime' => $file->getMimeType(),
                'real_mime' => $realMimeType,
                'filename' => $file->getClientOriginalName(),
                'ip' => $request->ip(),
            ]);
            return 'File type not allowed. Only images (JPEG, PNG, WebP, GIF, SVG) are accepted';
        }

        // Verify magic bytes (file signature)
        if (!$this->verifyFileSignature($file)) {
            return 'File signature verification failed';
        }

        return null; // Valid
    }

    public function validateAndStore(Request $request, string $fieldName, string $directory): ?string
    {
        $error = $this->validateUpload($request, $fieldName);
        if ($error) {
            throw new \InvalidArgumentException($error);
        }

        if (!$request->hasFile($fieldName)) {
            return null;
        }

        $file = $request->file($fieldName);

        // Generate safe unique filename with correct extension
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = uniqid('file_', true) . '.' . $extension;

        // Store in specified directory
        $path = $file->storeAs($directory, $filename, 'public');

        return $path;
    }

    private function getRealMimeTypa(string $filepath): string
    {
        if (!function_exists('finfo_open')) {
            return 'application/octet-stream';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $filepath);
        finfo_close($finfo);

        return $mime ?: 'application/octet-stream';
    }

    private function verifyFileSignature($file): bool
    {
        $realPath = $file->getRealPath();
        if (!$realPath || !is_file($realPath)) {
            return false;
        }

        $handle = fopen($realPath, 'rb');
        if (!$handle) {
            return false;
        }

        $bytes = fread($handle, 16);
        fclose($handle);

        if (strlen($bytes) < 4) {
            return false;
        }

        // JPEG signature
        if (substr($bytes, 0, 2) === "\xFF\xD8") {
            return true;
        }

        // PNG signature
        if (substr($bytes, 0, 8) === "\x89PNG\r\n\x1A\n") {
            return true;
        }

        // GIF signature
        if (substr($bytes, 0, 6) === "GIF87a" || substr($bytes, 0, 6) === "GIF89a") {
            return true;
        }

        // WebP signature
        if (substr($bytes, 0, 4) === "RIFF" && substr($bytes, 8, 4) === "WEBP") {
            return true;
        }

        // SVG - check if it's XML
        $content = file_get_contents($realPath, false, null, 0, 1024);
        if (stripos($content, '<?xml') !== false || stripos($content, '<svg') !== false) {
            return true;
        }

        return false;
    }

    public static function getAllowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    public static function getAllowedMimeTypes(): array
    {
        return self::ALLOWED_MIME_TYPES;
    }
}
