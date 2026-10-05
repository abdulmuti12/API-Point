<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Customer;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;

class AuthenticateCustomer
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (! $token = JWTAuth::parser()->setRequest($request)->parseToken()) {
                throw new UnauthorizedHttpException('jwt-auth', 'Token not provided');
            }

            // check() validates signature, expiry, AND blacklist — throws on any failure
            $payload = JWTAuth::setToken($token)->check(true);
            if (! $payload) {
                throw new UnauthorizedHttpException('jwt-auth', 'Token is invalid or blacklisted');
            }

            $customerId = $payload->get('sub');

            $customer = Customer::find($customerId);
            if (! $customer) {
                throw new UnauthorizedHttpException('jwt-auth', 'Customer not found');
            }

            Auth::guard('customer')->setUser($customer);
        } catch (TokenExpiredException $e) {
            throw new UnauthorizedHttpException('jwt-auth', 'Token has expired', $e, 401);
        } catch (TokenInvalidException $e) {
            throw new UnauthorizedHttpException('jwt-auth', 'Token is invalid', $e, 401);
        } catch (TokenBlacklistedException $e) {
            throw new UnauthorizedHttpException('jwt-auth', 'Token has been revoked', $e, 401);
        } catch (UnauthorizedHttpException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('jwt-auth', 'Authentication failed', $e, 401);
        }

        return $next($request);
    }
}
