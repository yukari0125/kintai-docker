<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class AttemptToAuthenticateUser
{
    /**
     * Attempt to authenticate a general user with custom validation messages.
     */
    public function handle(Request $request, Closure $next)
    {
        $authenticated = Auth::guard(config('fortify.guard'))->attempt([
            'email' => mb_strtolower((string) $request->input('email')),
            'password' => (string) $request->input('password'),
            'role' => User::ROLE_USER,
        ], $request->boolean('remember'));

        if (! $authenticated) {
            throw ValidationException::withMessages([
                Fortify::username() => 'ログイン情報が登録されていません',
            ]);
        }

        return $next($request);
    }
}
