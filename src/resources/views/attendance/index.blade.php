@extends('layouts.app')

@section('content')
<div class="main-shell page-clock page-attendance">
    <div class="clock-panel">
        <div class="status-pill">{{ $status }}</div>
        <div class="attendance-date">{{ $now->isoFormat('YYYY年M月D日(ddd)') }}</div>
        <div class="attendance-time">{{ $now->format('H:i') }}</div>

        <div class="attendance-actions">
            @if ($status === '勤務外')
                <form method="POST" action="{{ route('attendance.clock-in') }}">
                    @csrf
                    <button class="action-button" type="submit">出勤</button>
                </form>
            @endif

            @if ($status === '出勤中')
                <form method="POST" action="{{ route('attendance.clock-out') }}">
                    @csrf
                    <button class="action-button" type="submit">退勤</button>
                </form>

                <form method="POST" action="{{ route('attendance.break-start') }}">
                    @csrf
                    <button class="action-button action-button-sub" type="submit">休憩入</button>
                </form>
            @endif

            @if ($status === '休憩中')
                <form method="POST" action="{{ route('attendance.break-end') }}">
                    @csrf
                    <button class="action-button action-button-sub" type="submit">休憩戻</button>
                </form>
            @endif
        </div>

        @if ($status === '退勤済')
            <div class="attendance-finish-message">お疲れ様でした。</div>
        @endif
    </div>
</div>
@endsection
