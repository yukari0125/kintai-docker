<?php

namespace App\Providers;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Responses\LoginResponse;
use App\Http\Responses\LogoutResponse;
use App\Http\Responses\RegisterResponse;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController as FortifyAuthenticatedSessionController;
use Laravel\Fortify\Http\Controllers\RegisteredUserController as FortifyRegisteredUserController;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FortifyAuthenticatedSessionController::class, AuthenticatedSessionController::class);
        $this->app->bind(FortifyRegisteredUserController::class, RegisteredUserController::class);
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
        $this->app->singleton(LogoutResponseContract::class, LogoutResponse::class);
        $this->app->singleton(RegisterResponseContract::class, RegisterResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
