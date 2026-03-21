<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginAdminRequest;
use App\Http\Responses\AdminLogoutResponse;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the admin login page.
     */
    public function create(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Authenticate an admin user.
     */
    public function store(LoginAdminRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $authenticated = Auth::attempt([
            'email' => mb_strtolower($credentials['email']),
            'password' => $credentials['password'],
            'role' => User::ROLE_ADMIN,
        ], $request->boolean('remember'));

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => 'ログイン情報が登録されていません',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->route('admin.attendance.index');
    }

    /**
     * Logout an admin user.
     */
    public function destroy(Request $request, AdminLogoutResponse $response): \Laravel\Fortify\Contracts\LogoutResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $response;
    }
}
