<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCsrfOrigin
{
    /**
     * Handle an incoming request.
     *
     * Melindungi dari CSRF dengan memvalidasi Origin dan Referer header.
     * Hanya menerima request dari domain yang diizinkan.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya validasi untuk state-changing methods (POST, PUT, PATCH, DELETE)
        $method = $request->method();
        if (!in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next($request);
        }

        // Dapatkan daftar origin yang diizinkan dari config
        $allowedOrigins = config('cors.allowed_origins', []);
        $allowedOriginsPattern = config('cors.allowed_origins_patterns', []);

        // Ambil origin dari header
        $origin = $request->header('Origin');
        $referer = $request->header('Referer');

        // Jika tidak ada Origin atau Referer, izinkan untuk development (localhost)
        if (empty($origin) && empty($referer)) {
            return $next($request);
        }

        // Validasi Origin header
        if (!empty($origin)) {
            if ($this->isAllowedOrigin($origin, $allowedOrigins, $allowedOriginsPattern)) {
                return $next($request);
            }
        }

        // Validasi Referer (fallback)
        if (!empty($referer)) {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            if ($refererHost) {
                $refererScheme = parse_url($referer, PHP_URL_SCHEME) ?: 'http';
                $refererPort = parse_url($referer, PHP_URL_PORT);
                $refererOrigin = $refererScheme . '://' . $refererHost . ($refererPort ? ':' . $refererPort : '');
                if ($this->isAllowedOrigin($refererOrigin, $allowedOrigins, $allowedOriginsPattern)) {
                    return $next($request);
                }
            }
        }

        // Tidak ada validasi yang lolos
        return response()->json([
            'success' => false,
            'message' => 'Forbidden: Invalid Origin or Referer.',
            'status' => 403,
        ], 403);
    }

    /**
     * Check if origin is in allowed list
     */
    private function isAllowedOrigin(string $origin, array $allowedOrigins, array $allowedPatterns): bool
    {
        // Check exact match
        if (in_array($origin, $allowedOrigins)) {
            return true;
        }

        // Check pattern match
        foreach ($allowedPatterns as $pattern) {
            if (@preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }
}
