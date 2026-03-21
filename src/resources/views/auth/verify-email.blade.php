@extends('layouts.app')

@section('content')
<style>
    body,
    body.body-app-pages,
    body.body-verification-page {
        background: #ffffff !important;
    }
</style>

<div class="main-shell page-auth page-verify-email">
    <div class="auth-card">
        @if (session('status') === 'verification-link-sent')
            <div class="success-message">
                認証メールを再送しました。
            </div>
        @endif

        <div class="verify-email-panel">
            <p class="auth-copy auth-copy-tight">
                登録していただいたメールアドレスに認証メールを送付しました。
            </p>
            <p class="auth-copy auth-copy-sub">
                メール認証を完了してください。
            </p>

            <div class="verify-email-actions">
                <form method="GET" action="{{ route('verification.link') }}">
                    <button class="button button-block button-verify-primary" type="submit">認証はこちらから</button>
                </form>

                <form method="POST" action="{{ route('verification.send') }}">
                    @csrf
                    <button class="verify-email-resend" type="submit">認証メールを再送する</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
