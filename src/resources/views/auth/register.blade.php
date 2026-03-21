@extends('layouts.app')

@section('content')
<div class="main-shell page-auth page-register">
    <div class="auth-card">
        <h1 class="auth-title">会員登録</h1>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="field">
                <label class="label" for="name">名前</label>
                <input class="input" id="name" type="text" name="name" value="{{ old('name') }}">
                @error('name')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="email">メールアドレス</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}">
                @error('email')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="password">パスワード</label>
                <input class="input" id="password" type="password" name="password">
                @error('password')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label class="label" for="password_confirmation">パスワード確認</label>
                <input class="input" id="password_confirmation" type="password" name="password_confirmation">
                @error('password_confirmation')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <button class="button button-block" type="submit">登録する</button>
        </form>

        <a class="link" href="{{ route('login') }}">ログインはこちら</a>
    </div>
</div>
@endsection
