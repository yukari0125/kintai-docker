<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;

class AdminLogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse('', 204)
            : redirect()->route('admin.login');
    }
}
