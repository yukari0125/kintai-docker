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

4. アプリケーションキーを作成し、データベースを初期化します。

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate:fresh --seed
```
※ migrate:fresh --seed は既存のテーブルを削除し、再作成したうえでダミーデータを投入します。

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

## PHPUnitによるテスト

- 本アプリケーションでは、PHPUnit を利用してテストを実施しています。  
- 認証機能や主要機能について、自動テストで動作確認が可能です。

### テスト実行手順

#### 1. コンテナが起動していることを確認

```bash
docker compose up -d
```

#### 2. 全テストを実行

```bash
docker compose exec app php artisan test
```

#### 3. 特定のテストのみ実行

- 認証テストを実行する場合:

```bash
docker compose exec app php artisan test tests/Feature/Auth/AuthenticationTest.php
```

- テスト実行例
```bash
docker compose exec app php artisan test
```

- 実行結果例:

```bash
PASS  Tests\Unit\ExampleTest
PASS  Tests\Feature\Auth\AuthenticationTest


Tests: 10 passed
Time: 2.34s
```

### 補足
- テストは tests/Feature および tests/Unit ディレクトリに配置されています。
- データベースを使用するテストは、テスト実行時に自動的にリフレッシュされます。
- すべてのテストが成功することで、アプリケーションの基本動作を確認できます。

## 開発用メモ

- `src/.env` は Docker 構成向けに MySQL / MailHog 接続へ調整済みです。
- `app` コンテナ起動時に `storage` と `bootstrap/cache` の権限を開発用に補正します。

## 成果物
- ER図 SVG: [`docs/ER図.svg`](./docs/ER図.svg)


## テーブル仕様

### usersテーブル

| カラム名 | 型 | PK | UK | NOT NULL | FK |
|---------|----|----|----|----------|----|
| id | bigint | ○ |  | ○ |  |
| name | varchar(255) |  |  | ○ |  |
| email | varchar(255) |  | ○ | ○ |  |
| role | varchar(255) |  |  | ○ |  |
| email_verified_at | timestamp |  |  |  |  |
| password | varchar(255) |  |  | ○ |  |
| remember_token | varchar(100) |  |  |  |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |


### password_reset_tokensテーブル

| カラム名 | 型 | PK | UK | NOT NULL | FK |
| ---------- | ------------ | -- | -- | -------- | -- |
| email      | varchar(255) | ○  |    | ○        |    |
| token      | varchar(255) |    |    | ○        |    |
| created_at | timestamp    |    |    |          |    |


### sessionsテーブル

| カラム名 | 型 | PK | UK | NOT NULL | FK |
| ------------- | ------------ | -- | -- | -- | -------- |
| id            | varchar(255) | ○  |    | ○  |          |
| user_id       | bigint       |    |    |    | users.id |
| ip_address    | varchar(45)  |    |    |    |          |
| user_agent    | text         |    |    |    |          |
| payload       | longtext     |    |    | ○  |          |
| last_activity | integer      |    |    | ○  |          |


### attendancesテーブル

| カラム名 | 型 | PK | UK | NOT NULL | FK |
| ------------ | --------- | -- | -- | -- | -------- |
| id           | bigint    | ○  |    | ○  |          |
| user_id      | bigint    |    |    | ○  | users.id |
| work_date    | date      |    |    | ○  |          |
| clock_in_at  | timestamp |    |    |    |          |
| clock_out_at | timestamp |    |    |    |          |
| created_at   | timestamp |    |    |    |          |
| updated_at   | timestamp |    |    |    |          |

#### 制約
- UNIQUE(user_id, work_date)

### break_timesテーブル

| カラム名 | 型 | PK | UK | NOT NULL | FK |
| ------------- | --------- | -- | -- | -- | -------------- |
| id            | bigint    | ○  |    | ○  |                |
| attendance_id | bigint    |    |    | ○  | attendances.id |
| started_at    | timestamp |    |    | ○  |                |
| ended_at      | timestamp |    |    |    |                |
| created_at    | timestamp |    |    |    |                |
| updated_at    | timestamp |    |    |    |                |


### attendance_requestsテーブル

| カラム名 | 型 | PK | UK | NOT NULL | FK |
| ---------------------- | ------------ | -- | -- | -- | -------------- |
| id                     | bigint       | ○  |    | ○  |                |
| attendance_id          | bigint       |    |    | ○  | attendances.id |
| requested_clock_in_at  | timestamp    |    |    | ○  |                |
| requested_clock_out_at | timestamp    |    |    | ○  |                |
| requested_break_times  | json         |    |    | ○  |                |
| note                   | text         |    |    | ○  |                |
| status                 | varchar(255) |    |    | ○  |                |
| created_at             | timestamp    |    |    |    |                |
| updated_at             | timestamp    |    |    |    |                |

※ 本テーブル仕様は migration 定義と一致しています。