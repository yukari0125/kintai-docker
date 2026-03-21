# kintai-docker

勤怠管理アプリ要件シート向けの Laravel 開発環境です。

## 技術構成

- PHP 8.3
- Laravel 12
- Laravel Fortify
- MySQL 8.0
- Nginx
- MailHog
- Node 22
- Vite

## セットアップ

1. コンテナをビルドして起動します。

```bash
docker compose up -d --build
```

2. PHP 依存をインストールします。

```bash
docker compose exec app composer install
```

3. フロントエンド依存をインストールしてビルドします。

```bash
docker compose exec node npm install
docker compose exec node npm run build
```

4. アプリケーションキーを作成し、マイグレーションとシーディングを実行します。

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

## アクセス先

- アプリ: `http://localhost:8081`
- 一般ユーザーログイン: `http://localhost:8081/login`
- 管理者ログイン: `http://localhost:8081/admin/login`
- MailHog: `http://localhost:8025`
- MailHog SMTP: `mailhog:1025`
- MySQL: `127.0.0.1:3307`

## ログイン情報

- 管理者ユーザー: `admin@example.com` / `password123`
- 一般ユーザー: `user1@example.com` / `password123`
- 一般ユーザー: `user2@example.com` / `password123`

## ダミーデータ

`php artisan migrate --seed` で次のデータを投入します。

- 管理者 1 件
- 一般ユーザー 2 件
- 一般ユーザーごとに勤怠 5 日分
- 各勤怠の出勤時刻 `09:00`
- 各勤怠の退勤時刻 `18:00`
- 各勤怠の休憩時刻 `12:00-13:00`
- 各勤怠の備考 `出社勤務`

## 主な画面

- 一般ユーザー
  - 勤怠登録: `/attendance`
  - 勤怠一覧: `/attendance/list`
  - 勤怠詳細: `/attendance/detail/{attendance}`
  - 申請一覧: `/stamp_correction_request/list`
- 管理者
  - 勤怠一覧: `/admin/attendance/list`
  - スタッフ一覧: `/admin/staff/list`
  - スタッフ別勤怠一覧: `/admin/attendance/staff/{user}`
  - 申請一覧: `/stamp_correction_request/list`

## メール認証

- 一般ユーザーはメール認証後に勤怠画面へ遷移します。
- 認証メールは MailHog で確認できます。
- 未認証ユーザーが保護画面へアクセスした場合は認証誘導画面へ遷移します。

## テスト実行

全体:

```bash
docker compose exec app php artisan test
```

主要な認証テスト:

```bash
docker compose exec app php artisan test tests/Feature/Auth/AuthenticationTest.php
```

## 開発用メモ

- `src/.env` は Docker 構成向けに MySQL / MailHog 接続へ調整済みです。
- `app` コンテナ起動時に `storage` と `bootstrap/cache` の権限を開発用に補正します。
- Vite 開発サーバーを使う場合は次を実行します。

```bash
docker compose exec node npm run dev -- --host 0.0.0.0
```

## 成果物

- ER図 Markdown: [`docs/ER図.md`](./docs/ER図.md)
- ER図 draw.io: [`docs/ER図.drawio`](./docs/ER図.drawio)
- ER図 SVG: [`docs/ER図.svg`](./docs/ER図.svg)
- 画面キャプチャ: [`docs`](./docs)
