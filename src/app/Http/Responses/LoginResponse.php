<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return new JsonResponse('', 204);
        }

        $user = $request->user();

        if ($user instanceof User && $user->isGeneralUser() && ! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(Fortify::redirects('login'));
    }
}
