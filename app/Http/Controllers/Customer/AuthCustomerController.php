<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Customer\AuthCustomerService;

class AuthCustomerController extends BaseController
{
    /**
     * POST /api/customers/login-customer
     */
    public function login(Request $request, AuthCustomerService $service)
    {
        $result = $service->login($request->only('email', 'password'));

        if (isset($result['error'])) {
            if ($result['error'] === 'password_setup') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'needs_password_setup' => $result['needs_password_setup'] ?? true,
                    'setup_token' => $result['setup_token'],
                    'email' => $result['email'],
                ], 403);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        return $this->sendResponse($result['data'], 'Login Success');
    }

    /**
     * POST /api/customers/register
     */
    public function register(Request $request, AuthCustomerService $service)
    {
        $result = $service->register($request->all());

        if (isset($result['error'])) {
            $status = in_array($result['error'], ['Registration Failed']) ? 409 : 422;
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null,
                'duplicate_fields' => $result['duplicate_fields'] ?? null,
                'status' => $status,
            ], $status);
        }

        return $this->sendResponse(
            $result['customer'],
            'Registrasi berhasil. Password sementara telah dikirim ke email Anda. Silakan cek inbox untuk mengaktifkan akun.'
        );
    }

    /**
     * POST /api/customers/social-register-auth
     */
    public function socialRegister(Request $request, AuthCustomerService $service)
    {
        $result = $service->socialLogin($request->all(), 'register');

        if (isset($result['error'])) {
            $status = in_array($result['error'], ['Registration Failed']) ? 409 : 401;
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'errors' => $result['errors'] ?? null,
                'duplicate_fields' => $result['duplicate_fields'] ?? null,
                'status' => $status,
            ], $status);
        }

        return $this->sendResponse($result['data'], 'Social auth customer berhasil');
    }

    /**
     * POST /api/customers/social-login
     */
    public function socialLogin(Request $request, AuthCustomerService $service)
    {
        $result = $service->socialLogin($request->all(), 'login');

        if (isset($result['error'])) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
                'status' => 401,
            ], 401);
        }

        return $this->sendResponse($result['data'], 'Social login customer berhasil');
    }

    /**
     * POST /api/customers/logout
     */
    public function logout()
    {
        try {
            \Tymon\JWTAuth\Facades\JWTAuth::invalidate(\Tymon\JWTAuth\Facades\JWTAuth::getToken());
            return $this->sendResponse(null, 'Logout berhasil');
        } catch (\Exception $e) {
            return $this->sendError('Gagal logout', 'Terjadi kesalahan pada server');
        }
    }

    /**
     * POST /api/customers/resend-verification
     */
    public function resendVerification(Request $request, AuthCustomerService $service)
    {
        $request->validate(['email' => 'required|email']);

        $result = $service->resendVerification($request->only('email'));

        if (isset($result['error'])) {
            $status = $result['error'] === 'Not Found' ? 404 : 400;
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'email'   => $result['email'],
        ]);
    }

    /**
     * POST /api/customers/verify-email
     */
    public function verifyEmail(Request $request, AuthCustomerService $service)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
        ]);

        $result = $service->verifyEmail($request->only(['email', 'token']));

        if (isset($result['error'])) {
            $status = in_array($result['error'], ['Not Found']) ? 404 : 400;
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'email'   => $result['email'],
        ]);
    }

    /**
     * GET /api/customers/me
     */
    public function me()
    {
        $user = Auth::guard('customer')->user();

        if (!$user) {
            return $this->sendError('Unauthorized', 'User tidak ditemukan');
        }

        return $this->sendResponse([
            'id'                  => $user->id,
            'name'                => $user->name,
            'full_name'           => $user->full_name,
            'email'               => $user->email,
            'phone_number'        => $user->phone_number,
            'avatar'              => $user->avatar,
            'provider'            => $user->provider,
            'email_verified_at'   => $user->email_verified_at,
            'status'              => $user->status,
        ], 'Data user');
    }

    /**
     * POST /api/customers/change-password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password'     => 'required',
            'new_password'     => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = Auth::guard('customer')->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->old_password, $user->password)) {
            return $this->sendError('Password lama salah');
        }

        $user->password = \Illuminate\Support\Facades\Hash::make($request->new_password);
        $user->save();

        return $this->sendResponse(null, 'Password berhasil diubah');
    }

    /**
     * POST /api/customers/set-password
     * Set new password untuk first-login customer (admin-created atau register manual yang verify=0).
     */
    public function setPassword(Request $request, AuthCustomerService $service)
    {
        $result = $service->setPassword($request->all());

        if (isset($result['error'])) {
            $status = in_array($result['error'], ['Not Found']) ? 404 : 400;
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], $status);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'data'    => $result['data'],
        ]);
    }
}
