@extends('layouts.app')

@section('content')
<div class="main-shell page-admin-staff-list">
    <h1 class="page-heading"><span class="title">スタッフ一覧</span></h1>

        <div class="table-wrap">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>メールアドレス</th>
                        <th>月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($staff as $staffUser)
                        <tr>
                            <td>{{ $staffUser->name }}</td>
                            <td>{{ $staffUser->email }}</td>
                            <td><a class="table-link" href="{{ route('admin.staff.attendance', $staffUser) }}">詳細</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="empty-row">一般ユーザーがいません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
</div>
@endsection
