# ER図

```mermaid
erDiagram
    users ||--o{ attendances : has
    attendances ||--o{ break_times : has
    users ||--o{ attendance_requests : submits
    attendances ||--o{ attendance_requests : receives

    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar role
        timestamp email_verified_at
        varchar password
        timestamp created_at
        timestamp updated_at
    }

    attendances {
        bigint id PK
        bigint user_id FK
        date work_date
        timestamp clock_in_at
        timestamp clock_out_at
        text note
        timestamp created_at
        timestamp updated_at
    }

    break_times {
        bigint id PK
        bigint attendance_id FK
        timestamp started_at
        timestamp ended_at
        timestamp created_at
        timestamp updated_at
    }

    attendance_requests {
        bigint id PK
        bigint attendance_id FK
        bigint user_id FK
        timestamp requested_clock_in_at
        timestamp requested_clock_out_at
        json requested_break_times
        text note
        varchar status
        timestamp created_at
        timestamp updated_at
    }
```

## 補足

- `users` と `attendances` は `1:N`
- `attendances` と `break_times` は `1:N`
- `users` と `attendance_requests` は `1:N`
- `attendances` と `attendance_requests` は `1:N`
- `attendances` は `user_id + work_date` の複合ユニーク制約を持ち、1ユーザー1日1勤怠です
