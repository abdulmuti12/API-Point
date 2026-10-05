<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\Admin;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\Validator;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $credentials = $request->only(['email', 'password']);

        if (!$token = JWTAuth::attempt($credentials)) {
            return $this->sendError('Unauthorized', 'Email atau password salah');
        }

        // Update last_login timestamp
        $admin = Auth::user();
        $admin->last_login = now();
        $admin->save();

        return $this->sendResponse([
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->roles,
            'menus' => $this->getMenu(),
            'token' => $token,
        ], 'Login berhasil');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $admin->roles()->attach($request->role_id);

        return $this->sendResponse($admin, 'Register berhasil');
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

    public function me()
    {
        if (!Auth::check()) {
            return $this->sendError('Unauthorized', 'User not authenticated');
        }

        $data=Auth::user();
        $data['role']=Auth::user()->roles[0]->name;
        unset($data['roles']);

        return $this->sendResponse($data, 'Data user');

    }

    function getMenu(){

        $admin = Auth::user();

        $menus = $admin->roles[0]->menus->map(function ($menu) {
            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'route' => $menu->url,
            ];
        });

        return $menus;
    }

    public function sendResetLinkEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);
            $status = Password::broker('admins')->sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                // return response()->json(['message' => 'Reset link sent']);
                return $this->sendResponse(null, 'Reset link berhasil dikirim.');
            }

            // Kalau gagal tapi tidak melempar exception
            // return response()->json(['error' => __($status)], 400);
            return $this->sendError('Gagal mengirim reset link', __($status), 400);

        } catch (ValidationException $e) {
            // return response()->json([
            //     'errors' => $e->errors(),
            // ], 422);
            return $this->sendError('Validasi Gagal', $e->errors(), 422);
        } catch (Exception $e) {
            // return response()->json([
            //     'error' => 'Terjadi kesalahan pada server',
            // ], 500);
            return $this->sendError('Server Error', 'Terjadi kesalahan pada server');
        }
    }

    // Reset password dengan token
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        $status = Password::broker('admins')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        // return $status === Password::PASSWORD_RESET
        //     ? response()->json(['message' => 'Password berhasil direset.'])
        //     : response()->json(['message' => 'Token tidak valid atau sudah kadaluarsa.'], 400);

        if ($status === Password::PASSWORD_RESET) {
            return $this->sendResponse(null, 'Password berhasil direset.');
        } else {
            return $this->sendError('Reset Gagal', 'Token tidak valid atau sudah kadaluarsa.', 400);
        }
    }

    public function change_password(Request $request) {
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validasi Gagal', $validator->errors(), 422);
        }

        $admin = auth()->user();

        if (!$admin) {
            return $this->sendError('Unauthorized', 'User tidak ditemukan atau belum login.', 401);
        }

        if (!Hash::check($request->old_password, $admin->password)) {
            return $this->sendError('Password Lama Salah', 'Password lama tidak cocok.', 400);
        }

        $admin->password = Hash::make($request->new_password);
        $admin->save();

        return $this->sendResponse(null, 'Password berhasil diubah.');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->old_password, $user->password)) {
            return $this->sendError('Password lama salah');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->sendResponse(null, 'Password berhasil diubah');
    }
}
