@extends('layouts.app')

@section('content')
<div class="main-shell page-admin-requests">
    <h1 class="page-heading"><span class="title">申請一覧</span></h1>

        <div class="tab-nav">
            <a class="tab-link {{ $status === 'pending' ? 'tab-link-active' : '' }}" href="{{ route('admin.requests.index', ['status' => 'pending']) }}">承認待ち</a>
            <a class="tab-link {{ $status === 'approved' ? 'tab-link-active' : '' }}" href="{{ route('admin.requests.index', ['status' => 'approved']) }}">承認済み</a>
        </div>

        <div class="table-wrap">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>状態</th>
                        <th>名前</th>
                        <th>対象日</th>
                        <th>備考</th>
                        <th>申請日時</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($requests as $attendanceRequest)
                        <tr>
                            <td>{{ $attendanceRequest->statusLabel() }}</td>
                            <td>{{ $attendanceRequest->attendance->user->name }}</td>
                            <td>{{ $attendanceRequest->workDateLabel() }}</td>
                            <td>{{ $attendanceRequest->note }}</td>
                            <td>{{ $attendanceRequest->created_at->format('Y/m/d H:i') }}</td>
                            <td><a class="table-link" href="{{ route('admin.requests.show', $attendanceRequest) }}">詳細</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">申請はありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
</div>
@endsection
