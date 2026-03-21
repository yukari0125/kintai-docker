<?php

namespace App\Http\Controllers\Auth;

use App\Http\Requests\Auth\RegisterUserRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\RegisterViewResponse;

class RegisteredUserController extends Controller
{
    public function __construct(
        protected StatefulGuard $guard,
    ) {
    }

    public function create(Request $request): RegisterViewResponse
    {
        return app(RegisterViewResponse::class);
    }

    public function store(RegisterUserRequest $request, CreatesNewUsers $creator): RegisterResponse
    {
        event(new Registered($user = $creator->create($request->validated())));

        $this->guard->login($user, $request->boolean('remember'));

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return app(RegisterResponse::class);
    }
}
