@extends('layouts.app')

@section('content')
<div class="main-shell page-auth page-admin-login">
    <div class="auth-card">
        <h1 class="auth-title">管理者ログイン</h1>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf
            <div class="field">
                <label class="label" for="email">メールアドレス</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}">
            </div>

            <div class="field">
                <label class="label" for="password">パスワード</label>
                <input class="input" id="password" type="password" name="password">
            </div>

            <button class="button button-block" type="submit">管理者ログインする</button>
        </form>
    </div>
</div>
@endsection
