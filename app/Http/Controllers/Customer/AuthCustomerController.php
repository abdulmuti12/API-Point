<?php

namespace App\Http\Controllers\Customer;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\CustomerWelcomeMail;

class AuthCustomerController extends BaseController
{
    public function login(Request $request)
    {

        $credentials = $request->only('email', 'password');

        if (!$token = Auth::guard('customer')->attempt($credentials)) {
            return response()->json(['success'=>false,'message' => 'Invalid credentials'], 401);
        }


        $user = Auth::guard('customer')->user();

        // Customer yang dibuat admin (verify=0) -> minta setup password baru
        if ($user->verify != 1) {
            // Generate short-lived setup token (TTL 1 jam)
            $setupToken = hash('sha256', $user->email . now()->timestamp . Str::random(16));
            \Cache::put('set_password_' . $user->email, [
                'token' => $setupToken,
                'expires_at' => now()->addHours(1),
            ], now()->addHours(1));

            // Logout dulu (invalidate token JWT yang baru dibuat) karena belum boleh login penuh
            try {
                JWTAuth::invalidate(JWTAuth::getToken());
            } catch (\Exception $e) {
                // ignore
            }

            return response()->json([
                'success' => false,
                'message' => 'Silakan set password baru untuk mengaktifkan akun Anda.',
                'needs_password_setup' => true,
                'setup_token' => $setupToken,
                'email' => $user->email,
            ], 403);
        }

        return $this->sendResponse([
            "id" => $user->id,
            "username" => $user->name,
            "email" => $user->email,
            "token" => $token
        ], 'Login Success');

        // $credentials = $request->only(['email', 'password']);

        // if (!$token = Auth::guard('customer')->attempt($credentials)) {
        //     return $this->sendError('Unauthorized', 'Email atau password salah');
        // }

        // $user = Auth::guard('customers')->user();
        // dd($user);

        // return $this->sendResponse([
        //     'id' => $user->id,
        //     'name' => $user->name,
        //     'email' => $user->email,
        //     'role' => 'customer',
        //     'token' => $token,
        // ], 'Login customer berhasil');
    }
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'full_name' => 'nullable|string',
            'email' => 'required|email',
            'phone_number' => 'required|string|max:15',
        ]);

        // Cek duplicate email
        $existingEmail = Customer::where('email', $request->email)->first();
        if ($existingEmail) {
            return response()->json([
                'success' => false,
                'message' => 'Registration Failed. Email is already registered',
                'errors' => [
                    'email' => ['Email sudah terdaftar, silakan gunakan email lain.'],
                ],
                'duplicate_fields' => ['email'],
                'status' => 409,
            ], 409);
        }

        // Cek duplicate phone_number
        $existingPhone = Customer::where('phone_number', $request->phone_number)->first();
        if ($existingPhone) {
            return response()->json([
                'success' => false,
                'message' => 'Registration Failed. Phone number is already registered',
                'errors' => [
                    'phone_number' => ['Nomor telepon sudah terdaftar, silakan gunakan nomor lain.'],
                ],
                'duplicate_fields' => ['phone_number'],
                'status' => 409,
            ], 409);
        }

        // Generate temporary password (random 10 chars)
        $tempPassword = Str::random(10);

        $customer = Customer::create([
            'name' => $request->name,
            'full_name' => $request->full_name ?? $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($tempPassword),
            'status' => 'active',
            'verify' => 0, // belum aktif sampai user set password sendiri via /set-password
            'time' => now(),
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
            Log::error('Failed to send welcome email to customer: '.$customer->email, [
                'error' => 'Terjadi kesalahan pada server',
            ]);
        }

        // TIDAK generate JWT token — customer belum boleh login penuh sampai set password baru
        return $this->sendResponse([
            'id' => $customer->id,
            'username' => $customer->name,
            'email' => $customer->email,
        ], 'Registrasi berhasil. Password sementara telah dikirim ke email Anda. Silakan cek inbox untuk mengaktifkan akun.');
    }



    public function socialRegister(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:google',
            'id_token' => 'nullable|string',
            'code' => 'nullable|string',
            'phone_number' => 'nullable|string|max:15',
        ]);

        // Jika dapat code (dari flow: 'auth-code'), tuker ke id_token dulu
        if ($request->filled('code')) {
            $idToken = $this->exchangeCodeForIdToken($request->code);
            if (!$idToken) {
                return $this->sendError('Unauthorized', 'Gagal menukar code ke token Google');
            }
        } else {
            $idToken = $request->id_token;
        }

        $googleUser = $this->verifyGoogleIdToken($idToken);
        if (!$googleUser) {
            return $this->sendError('Unauthorized', 'Google token tidak valid');
        }

        $provider = 'google';
        $providerId = $googleUser['sub'];
        $email = $googleUser['email'] ?? null;
        $name = $googleUser['name'] ?? $request->input('name', 'Google User');
        $avatar = $googleUser['picture'] ?? $request->input('avatar');

        if (!$email) {
            return $this->sendError('Unauthorized', 'Email Google tidak ditemukan');
        }

        // Cek duplicate phone_number JIKA email belum ada di database
        // (Kalau email sudah ada, berarti ini social login ke akun existing - boleh lanjut)
        $emailExists = Customer::where('email', $email)->exists();
        if (!$emailExists && $request->filled('phone_number')) {
            $phoneExists = Customer::where('phone_number', $request->phone_number)->exists();
            if ($phoneExists) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registration Failed. Phone number is already registered',
                    'errors' => [
                        'phone_number' => ['Nomor telepon sudah terdaftar, silakan gunakan nomor lain.'],
                    ],
                    'duplicate_fields' => ['phone_number'],
                    'status' => 409,
                ], 409);
            }
        }

        // Cek apakah sudah pernah login dengan Google sebelumnya (provider_id match)
        $customer = Customer::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (!$customer) {
            $customer = Customer::where('email', $email)->first();
        }

        if (!$customer) {
            $customer = Customer::create([
                'name' => $name,
                'full_name' => $name,
                'email' => $email,
                'phone_number' => $request->phone_number ?? $this->generateSocialPhoneNumber(),
                'password' => Hash::make(Str::random(32)),
                'provider' => $provider,
                'provider_id' => $providerId,
                'avatar' => $avatar,
                'verify' => 1,
                'email_verified_at' => now(),
                'status' => 'active',
                'time' => now(),
            ]);
        } else {
            $customer->update([
                'name' => $name,
                'full_name' => $customer->full_name ?? $name,
                'provider' => $provider,
                'provider_id' => $providerId,
                'avatar' => $avatar ?? $customer->avatar,
                'email_verified_at' => $customer->email_verified_at ?? now(),
            ]);
        }

        $this->invalidateOldTokens($customer);
        $token = Auth::guard('customer')->login($customer);

        return $this->sendResponse([
            'id' => $customer->id,
            'username' => $customer->name,
            'email' => $customer->email,
            'provider' => $customer->provider,
            'token' => $token,
        ], 'Social auth customer berhasil');
    }

    public function socialLogin(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:google',
            'id_token' => 'required|string',
        ]);

        $googleUser = $this->verifyGoogleIdToken($request->id_token);
        if (!$googleUser) {
            return $this->sendError('Unauthorized', 'Google token tidak valid');
        }

        $provider = 'google';
        $providerId = $googleUser['sub'];
        $email = $googleUser['email'] ?? null;
        $name = $googleUser['name'] ?? null;
        $avatar = $googleUser['picture'] ?? null;

        $customer = Customer::where('provider', $provider)
            ->where('provider_id', $providerId)
            ->first();

        if (!$customer && $email) {
            $customer = Customer::where('email', $email)->first();
        }

        if (!$customer) {
            return $this->sendError('Unauthorized', 'Akun social belum terdaftar. Silakan register social terlebih dahulu.');
        }

        $customer->update([
            'name' => $name ?? $customer->name,
            'full_name' => $customer->full_name ?? $name,
            'provider' => $provider,
            'provider_id' => $providerId,
            'avatar' => $avatar ?? $customer->avatar,
            'email_verified_at' => $customer->email_verified_at ?? now(),
            'status' => $customer->status ?? 'active',
            'time' => now(),
        ]);

        $this->invalidateOldTokens($customer);
        $token = Auth::guard('customer')->login($customer);

        return $this->sendResponse([
            'id' => $customer->id,
            'username' => $customer->name,
            'email' => $customer->email,
            'provider' => $customer->provider,
            'token' => $token,
        ], 'Social login customer berhasil');
    }

    private function invalidateOldTokens(Customer $customer): void
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
        $googleClientId = config('services.google.client_id');
        $googleClientSecret = config('services.google.client_secret');

        if (!$googleClientId || !$googleClientSecret) {
            return null;
        }

        $response = Http::asForm()->timeout(10)
            ->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $googleClientId,
                'client_secret' => $googleClientSecret,
                'redirect_uri' => 'postmessage',
                'grant_type' => 'authorization_code',
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

    public function logout()
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return $this->sendResponse(null, 'Logout berhasil');
        } catch (\Exception $e) {
            return $this->sendError('Gagal logout', 'Terjadi kesalahan pada server');
        }

    }

    /**
     * Send email verification link to user's email
     */
    public function resendVerification(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = Customer::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan',
            ], 404);
        }

        if ($user->verify == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda sudah terverifikasi. Silakan login.',
            ], 400);
        }

        // Generate verification token (hash of email + random string)
        $token = hash('sha256', $user->email . now()->timestamp . Str::random(16));

        // Store token temporarily (will be cleaned on verify)
        \Cache::put('email_verify_' . $user->email, [
            'token' => $token,
            'expires_at' => now()->addHours(24),
        ], now()->addHours(24));

        // Send verification email
        try {
            \Mail::to($user->email)->send(new \App\Mail\CustomerVerificationMail($user->name, $user->email, $token));
        } catch (\Exception $e) {
            \Log::error('Failed to send verification email to ' . $user->email, [
                'error' => 'Terjadi kesalahan pada server'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Link verifikasi telah dikirim ke ' . $user->email,
            'email' => $user->email,
        ]);
    }

    /**
     * Verify user email via token
     */
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        $user = Customer::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak ditemukan',
            ], 404);
        }

        if ($user->verify == 1) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda sudah terverifikasi.',
            ], 400);
        }

        // Check token validity
        $storedToken = \Cache::get('email_verify_' . $request->email);
        if (!$storedToken || $storedToken['token'] !== $request->token || $storedToken['expires_at']->lessThan(now())) {
            // Reject expired or invalid tokens
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau kadaluarsa',
            ], 400);
        }

        // Verify the user
        $user->verify = 1;
        $user->email_verified_at = now();
        $user->save();

        // Remove stored token
        \Cache::forget('email_verify_' . $request->email);

        return response()->json([
            'success' => true,
            'message' => 'Email Anda berhasil diverifikasi. Silakan login.',
            'email' => $user->email,
        ]);
    }

    public function me()
    {
        $user = Auth::guard('customer')->user();

        if (!$user) {
            return $this->sendError('Unauthorized', 'User tidak ditemukan');
        }

        return $this->sendResponse([
            'id' => $user->id,
            'name' => $user->name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
            'avatar' => $user->avatar,
            'provider' => $user->provider,
            'email_verified_at' => $user->email_verified_at,
            'status' => $user->status,
        ], 'Data user');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = Auth::guard('customer')->user();
        // echo $user->password;
        if (!Hash::check($request->old_password, $user->password)) {
            return $this->sendError('Password lama salah');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->sendResponse(null, 'Password berhasil diubah');
    }

    /**
     * Set new password untuk first-login customer (admin-created atau register manual yang verify=0).
     * Setelah berhasil, verify=1 dan customer langsung auto-login.
     */
    public function setPassword(Request $request)
    {
        $request->validate([
            'setup_token'     => 'required|string',
            'email'           => 'required|email',
            'new_password'    => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        // Validasi token dari cache
        $stored = \Cache::get('set_password_' . $request->email);
        if (!$stored || $stored['token'] !== $request->setup_token || $stored['expires_at']->lessThan(now())) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau kadaluarsa. Silakan login ulang.',
            ], 400);
        }

        $user = Customer::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan',
            ], 404);
        }

        // Set password baru
        $user->password = Hash::make($request->new_password);

        // Aktifkan verifikasi
        $user->verify = 1;
        $user->email_verified_at = $user->email_verified_at ?? now();
        $user->save();

        // Hapus token dari cache
        \Cache::forget('set_password_' . $request->email);

        // Auto-login: generate JWT token
        $token = Auth::guard('customer')->login($user);

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diatur. Akun Anda telah aktif.',
            'data' => [
                'id'       => $user->id,
                'username' => $user->name,
                'email'    => $user->email,
                'token'    => $token,
            ],
        ]);
    }

}
