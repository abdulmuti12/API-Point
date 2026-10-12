<?php

namespace App\Services\Customer;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\CustomerWelcomeMail;
use App\Mail\CustomerVerificationMail;

class AuthCustomerService
{
    /**
     * Login customer via email/password.
     * Return:
     *  - ['token' => ..., 'user' => ..., 'success' => true] bila sukses
     *  - ['error' => ..., 'message' => ...] bila gagal
     *  - ['error' => 'password_setup', 'setup_token' => ..., 'email' => ...] bila customer belum set password
     */
    public function login(array $credentials): array
    {
        $user = Customer::where('email', $credentials['email'] ?? null)->first();
        if (!$user || !Hash::check($credentials['password'] ?? '', $user->password)) {
            return ['error' => 'Unauthorized', 'message' => 'Invalid credentials'];
        }

        // Customer yang dibuat admin (verify=0) -> minta setup password baru
        if ($user->verify != 1) {
            $setupToken = hash('sha256', $user->email . now()->timestamp . Str::random(16));
            Cache::put('set_password_' . $user->email, [
                'token'      => $setupToken,
                'expires_at' => now()->addHours(1),
            ], now()->addHours(1));

            // Logout dulu (invalidate token JWT yang baru dibuat) karena belum boleh login penuh
            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (\Exception $e) {
                // ignore
            }

            return [
                'error'         => 'password_setup',
                'message'       => 'Silakan set password baru untuk mengaktifkan akun Anda.',
                'needs_password_setup' => true,
                'setup_token'   => $setupToken,
                'email'         => $user->email,
            ];
        }

        $token = Auth::guard('customer')->login($user);

        return [
            'success' => true,
            'data'    => [
                'id'       => $user->id,
                'username' => $user->name,
                'email'    => $user->email,
                'token'    => $token,
            ],
        ];
    }

    /**
     * Register customer baru (manual, via email).
     * Return:
     *  - ['customer' => Customer] bila sukses
     *  - ['error' => ..., 'message' => ...] bila gagal
     */
    public function register(array $request): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request, [
            'name'         => 'required|string',
            'full_name'    => 'nullable|string',
            'email'        => 'required|email',
            'phone_number' => 'required|string|max:15',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        // Cek duplicate email
        $existingEmail = Customer::where('email', $request['email'])->first();
        if ($existingEmail) {
            return [
                'error'   => 'Registration Failed',
                'message' => 'Email is already registered',
                'errors'  => [
                    'email' => ['Email sudah terdaftar, silakan gunakan email lain.'],
                ],
                'duplicate_fields' => ['email'],
            ];
        }

        // Cek duplicate phone_number
        $existingPhone = Customer::where('phone_number', $request['phone_number'])->first();
        if ($existingPhone) {
            return [
                'error'   => 'Registration Failed',
                'message' => 'Phone number is already registered',
                'errors'  => [
                    'phone_number' => ['Nomor telepon sudah terdaftar, silakan gunakan nomor lain.'],
                ],
                'duplicate_fields' => ['phone_number'],
            ];
        }

        // Generate temporary password (random 10 chars)
        $tempPassword = Str::random(10);

        $customer = Customer::create([
            'name'         => $request['name'],
            'full_name'    => $request['full_name'] ?? $request['name'],
            'email'        => $request['email'],
            'phone_number' => $request['phone_number'],
            'password'     => Hash::make($tempPassword),
            'status'       => 'active',
            'verify'       => 0, // belum aktif sampai user set password sendiri via /set-password
            'time'         => now(),
        ]);

        // Kirim email berisi password sementara (sama seperti admin create-user di halaman order)
        try {
            Mail::to($customer->email)->send(
                new CustomerWelcomeMail(
                    $customer->full_name,
                    $customer->email,
                    $tempPassword
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send welcome email to customer: ' . $customer->email, [
                'error' => 'Terjadi kesalahan pada server',
            ]);
        }

        // TIDAK generate JWT token — customer belum boleh login penuh sampai set password baru
        return [
            'customer' => [
                'id'       => $customer->id,
                'username' => $customer->name,
                'email'    => $customer->email,
            ],
        ];
    }

    /**
     * Set new password untuk first-login customer (admin-created atau register manual yang verify=0).
     * Setelah berhasil, verify=1 dan customer langsung auto-login.
     *
     * Return:
     *  - ['success' => true, 'token' => ..., 'email' => ...] bila sukses
     *  - ['error' => ..., 'message' => ...] bila gagal
     */
    public function setPassword(array $request): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request, [
            'setup_token'      => 'required|string',
            'email'           => 'required|email',
            'new_password'    => 'required|min:8',
            'confirm_password'=> 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        // Validasi token dari cache
        $stored = Cache::get('set_password_' . $request['email']);
        if (!$stored || $stored['token'] !== $request['setup_token'] || $stored['expires_at']->lessThan(now())) {
            return ['error' => 'Token Error', 'message' => 'Token tidak valid atau kadaluarsa. Silakan login ulang.'];
        }

        $user = Customer::where('email', $request['email'])->first();
        if (!$user) {
            return ['error' => 'Not Found', 'message' => 'User tidak ditemukan'];
        }

        // Set password baru
        $user->password = Hash::make($request['new_password']);

        // Aktifkan verifikasi
        $user->verify = 1;
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();

        // Hapus token dari cache
        Cache::forget('set_password_' . $request['email']);

        // Auto-login: generate JWT token
        $token = Auth::guard('customer')->login($user);

        return [
            'success' => true,
            'message' => 'Password berhasil diatur. Akun Anda telah aktif.',
            'data'    => [
                'id'       => $user->id,
                'username' => $user->name,
                'email'    => $user->email,
                'token'    => $token,
            ],
        ];
    }

    /**
     * Social login / register via Google (id_token atau code flow).
     * Return:
     *  - ['success' => true, 'data' => ...] bila sukses
     *  - ['error' => ..., 'message' => ...] bila gagal
     */
    public function socialLogin(array $request, string $mode = 'login'): array
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request, [
            'provider'     => 'required|string|in:google',
            'id_token'     => 'nullable|string',
            'code'         => 'nullable|string',
            'phone_number' => 'nullable|string|max:15',
        ]);

        if ($validator->fails()) {
            return ['error' => 'Validation Error', 'errors' => $validator->errors()];
        }

        // Jika dapat code (dari flow: 'auth-code'), tuker ke id_token dulu
        if ($request['code'] ?? null) {
            $idToken = $this->exchangeCodeForIdToken($request['code']);
            if (!$idToken) {
                return ['error' => 'Unauthorized', 'message' => 'Gagal menukar code ke token Google'];
            }
        } else {
            $idToken = $request['id_token'] ?? null;
        }

        if (!$idToken) {
            return ['error' => 'Unauthorized', 'message' => 'Google token tidak valid'];
        }

        $googleUser = $this->verifyGoogleIdToken($idToken);
        if (!$googleUser) {
            return ['error' => 'Unauthorized', 'message' => 'Google token tidak valid'];
        }

        $provider   = 'google';
        $providerId = $googleUser['sub'];
        $email      = $googleUser['email'] ?? null;
        $name       = $googleUser['name'] ?? ($request['name'] ?? 'Google User');
        $avatar     = $googleUser['picture'] ?? ($request['avatar'] ?? null);

        if (!$email) {
            return ['error' => 'Unauthorized', 'message' => 'Email Google tidak ditemukan'];
        }

        if ($mode === 'register') {
            // Cek duplicate phone_number JIKA email belum ada di database
            $emailExists = Customer::where('email', $email)->exists();
            if (!$emailExists && !empty($request['phone_number'])) {
                $phoneExists = Customer::where('phone_number', $request['phone_number'])->exists();
                if ($phoneExists) {
                    return [
                        'error'   => 'Registration Failed',
                        'message' => 'Phone number is already registered',
                        'errors'  => [
                            'phone_number' => ['Nomor telepon sudah terdaftar, silakan gunakan nomor lain.'],
                        ],
                        'duplicate_fields' => ['phone_number'],
                    ];
                }
            }
        }

        // Cek apakah sudah pernah login dengan Google sebelumnya (provider_id match)
        $customer = Customer::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (!$customer && $email) {
            $customer = Customer::where('email', $email)->first();
        }

        if (!$customer && $mode === 'register') {
            $customer = Customer::create([
                'name'              => $name,
                'full_name'         => $name,
                'email'             => $email,
                'phone_number'      => $request['phone_number'] ?? $this->generateSocialPhoneNumber(),
                'password'          => Hash::make(Str::random(32)),
                'provider'          => $provider,
                'provider_id'       => $providerId,
                'avatar'            => $avatar,
                'verify'            => 1,
                'email_verified_at' => now(),
                'status'            => 'active',
                'time'              => now(),
            ]);
        }

        if (!$customer) {
            return ['error' => 'Unauthorized', 'message' => 'Akun social belum terdaftar. Silakan register social terlebih dahulu.'];
        }

        $customer->update([
            'name'              => $name,
            'full_name'         => $customer->full_name ?? $name,
            'provider'          => $provider,
            'provider_id'       => $providerId,
            'avatar'            => $avatar ?? $customer->avatar,
            'email_verified_at' => $customer->email_verified_at ?? now(),
            'status'            => $customer->status ?? 'active',
            'time'              => now(),
        ]);

        $this->invalidateOldTokens();
        $token = Auth::guard('customer')->login($customer);

        return [
            'success' => true,
            'data'    => [
                'id'        => $customer->id,
                'username'  => $customer->name,
                'email'     => $customer->email,
                'provider'  => $customer->provider,
                'token'     => $token,
            ],
        ];
    }

    /**
     * Resend email verification link.
     * Return:
     *  - ['success' => true] bila email terkirim
     *  - ['error' => ..., 'message' => ...] bila email tak dikenal / sudah terverifikasi
     */
    public function resendVerification(array $request): array
    {
        $user = Customer::where('email', $request['email'])->first();

        if (!$user) {
            return ['error' => 'Not Found', 'message' => 'Email tidak ditemukan'];
        }

        if ($user->verify == 1) {
            return ['error' => 'Already Verified', 'message' => 'Akun Anda sudah terverifikasi. Silakan login.'];
        }

        // Generate verification token (hash of email + random string)
        $token = hash('sha256', $user->email . now()->timestamp . Str::random(16));

        // Store token temporarily (will be cleaned on verify)
        Cache::put('email_verify_' . $user->email, [
            'token'      => $token,
            'expires_at' => now()->addHours(24),
        ], now()->addHours(24));

        // Send verification email
        try {
            Mail::to($user->email)->send(new CustomerVerificationMail($user->name, $user->email, $token));
        } catch (\Exception $e) {
            Log::error('Failed to send verification email to ' . $user->email, [
                'error' => 'Terjadi kesalahan pada server'
            ]);
        }

        return [
            'success' => true,
            'message' => 'Link verifikasi telah dikirim ke ' . $user->email,
            'email'   => $user->email,
        ];
    }

    /**
     * Verify email via token.
     * Return:
     *  - ['success' => true] bila verifikasi berhasil
     *  - ['error' => ..., 'message' => ...] bila token tidak valid / email sudah terverifikasi
     */
    public function verifyEmail(array $request): array
    {
        $user = Customer::where('email', $request['email'])->first();

        if (!$user) {
            return ['error' => 'Not Found', 'message' => 'Email tidak ditemukan'];
        }

        if ($user->verify == 1) {
            return ['error' => 'Already Verified', 'message' => 'Akun Anda sudah terverifikasi.'];
        }

        // Check token validity
        $storedToken = Cache::get('email_verify_' . $request['email']);
        if (!$storedToken || $storedToken['token'] !== $request['token'] || $storedToken['expires_at']->lessThan(now())) {
            // Reject expired or invalid tokens
            return ['error' => 'Invalid Token', 'message' => 'Token tidak valid atau kadaluarsa'];
        }

        // Verify the user
        $user->verify = 1;
        $user->email_verified_at = now();
        $user->save();

        // Remove stored token
        Cache::forget('email_verify_' . $request['email']);

        return [
            'success' => true,
            'message' => 'Email Anda berhasil diverifikasi. Silakan login.',
            'email'   => $user->email,
        ];
    }

    private function invalidateOldTokens(): void
    {
        // Invalidate all existing tokens for this customer so only the newest session stays valid
        try {
            $currentToken = JWTAuth::getToken();
            if ($currentToken) {
                JWTAuth::invalidate($currentToken);
            }
        } catch (\Exception $e) {
            // No active token to invalidate — continuing with new session
        }
    }

    private function generateSocialPhoneNumber(): string
    {
        do {
            $phone = '9' . str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
        } while (Customer::where('phone_number', $phone)->exists());

        return $phone;
    }

    private function exchangeCodeForIdToken(string $code): ?string
    {
        $googleClientId     = config('services.google.client_id');
        $googleClientSecret = config('services.google.client_secret');

        if (!$googleClientId || !$googleClientSecret) {
            return null;
        }

        $response = Http::asForm()->timeout(10)
            ->post('https://oauth2.googleapis.com/token', [
                'code'           => $code,
                'client_id'      => $googleClientId,
                'client_secret'  => $googleClientSecret,
                'redirect_uri'   => 'postmessage',
                'grant_type'     => 'authorization_code',
            ]);

        if (!$response->ok()) {
            return null;
        }

        $data = $response->json();
        return $data['id_token'] ?? null;
    }

    private function verifyGoogleIdToken(string $idToken): ?array
    {
        $googleClientId = config('services.google.client_id');
        if (!$googleClientId) {
            return null;
        }

        $response = Http::timeout(10)
            ->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken,
            ]);

        if (!$response->ok()) {
            return null;
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return null;
        }

        if (($payload['aud'] ?? null) !== $googleClientId) {
            return null;
        }

        if (($payload['iss'] ?? null) !== 'https://accounts.google.com' && ($payload['iss'] ?? null) !== 'accounts.google.com') {
            return null;
        }

        return $payload;
    }
}
