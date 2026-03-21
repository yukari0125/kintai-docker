<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_requires_name(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください',
        ]);
    }

    public function test_registration_requires_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_registration_requires_password(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_registration_requires_minimum_password_length(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください',
        ]);
    }

    public function test_registration_requires_password_confirmation_to_match(): void
    {
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors([
            'password_confirmation' => 'パスワードと一致しません',
        ]);
    }

    public function test_registration_authenticates_general_user_and_redirects_to_attendance(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'user@example.com',
            'role' => User::ROLE_USER,
        ]);
        Notification::assertSentTo(User::first(), VerifyEmail::class);
    }

    public function test_login_requires_email(): void
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_login_requires_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_login_shows_error_when_credentials_are_invalid(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function test_general_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/attendance');
        $this->assertAuthenticated();
    }

    public function test_unverified_general_user_is_redirected_to_email_verification_notice_after_login(): void
    {
        User::factory()->unverified()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
            'role' => User::ROLE_USER,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
    }

    public function test_unverified_general_user_cannot_access_attendance_page(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_verification_notice_can_resend_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create([
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->post(route('verification.send'));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'verification-link-sent');
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verification_notice_has_link_to_verification_site(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertSee('認証はこちらから');
        $response->assertSee('action="'.route('verification.link').'"', false);
    }

    public function test_general_user_is_redirected_to_attendance_after_completing_email_verification(): void
    {
        $user = User::factory()->unverified()->create([
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->get(route('verification.link'));

        $response->assertOk();
        $response->assertSee('勤務外');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_general_user_sees_logout_button_in_header(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertOk();
        $response->assertSee('ログアウト');
        $response->assertSee('action="'.route('logout').'"', false);
    }

    public function test_general_user_can_logout(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_login_requires_email(): void
    {
        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください',
        ]);
    }

    public function test_admin_login_requires_password(): void
    {
        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください',
        ]);
    }

    public function test_admin_login_shows_error_when_credentials_are_invalid(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません',
        ]);
    }

    public function test_admin_can_login_from_admin_login_page(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password123',
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.attendance.index'));
        $this->assertAuthenticated();
    }

    public function test_admin_sees_logout_button_in_header(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertOk();
        $response->assertSee('ログアウト');
        $response->assertSee('action="'.route('admin.logout').'"', false);
    }

    public function test_admin_can_logout(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.logout'));

        $response->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
