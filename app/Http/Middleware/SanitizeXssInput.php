<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeXssInput
{
    /**
     * Handle an incoming request.
     *
     * Melindungi dari XSS dengan membersihkan semua input string.
     * Mengescape HTML entities dan menghapus tag script.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya proses untuk state-changing methods
        $method = $request->method();
        if (!in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Sanitize input data
        $sanitized = $this->sanitizeArray($request->all());

        // Replace input with sanitized data
        if ($request->isJson()) {
            $request->replace($sanitized);
        } else {
            // For form requests, sanitize both input and request
            $request->merge($sanitized);
        }

        return $next($request);
    }

    /**
     * Recursively sanitize array input
     */
    private function sanitizeArray(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            // Skip file uploads
            if ($value instanceof \Illuminate\Http\UploadedFile) {
                $sanitized[$key] = $value;
                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value);
            } elseif (is_string($value)) {
                $sanitized[$key] = $this->sanitizeString($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        return $sanitized;
    }

    /**
     * Sanitize a single string value
     */
    private function sanitizeString(string $value): string
    {
        // Remove null bytes
        $value = str_replace(chr(0), '', $value);

        // Decode HTML entities first to prevent double encoding
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Remove dangerous tags and attributes
        $value = preg_replace('/<\s*script\b[^>]*>(.*?)<\s*\/\s*script\s*>/is', '', $value);
        $value = preg_replace('/<\s*(\b(?:script|iframe|object|embed|form|input|link|meta|base|applet|video|audio|source|background|body|div|span|marquee|layer|bleg))[^>]*>/i', '', $value);
        $value = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        $value = preg_replace('/on\w+\s*=\s*[^\s>]*/i', '', $value);

        // Re-encode HTML entities to prevent XSS
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Trim whitespace
        return trim($value);
    }
}
