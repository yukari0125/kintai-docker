@extends('layouts.app')

@section('content')
@php
    $breakTimes = collect($attendanceRequest->requested_break_times)
        ->map(fn (array $breakTime) => [
            'label' => null,
            'start' => $breakTime['start'] ?? '',
            'end' => $breakTime['end'] ?? '',
        ]);

    while ($breakTimes->count() < 2) {
        $breakTimes->push([
            'label' => null,
            'start' => '',
            'end' => '',
        ]);
    }
@endphp

<div class="main-shell page-admin-request-detail">
    <h1 class="page-heading"><span class="title">勤怠詳細</span></h1>
    <span class="sr-only">管理者申請詳細</span>

    @if (session('message'))
        <div class="success-message">{{ session('message') }}</div>
    @endif

    <div class="detail-sheet">
        <div class="detail-row">
            <div class="detail-label">名前</div>
            <div class="detail-value">{{ $attendanceRequest->attendance->user->name }}</div>
        </div>

        <div class="detail-row">
            <div class="detail-label">日付</div>
            <div class="detail-value">
                <div class="time-range">
                    <span class="request-time">{{ $attendanceRequest->attendance->work_date->format('Y年') }}</span>
                    <span class="time-separator time-separator-hidden">〜</span>
                    <span class="request-time">{{ $attendanceRequest->attendance->work_date->locale('ja')->isoFormat('M月D日') }}</span>
                </div>
            </div>
        </div>

        <div class="detail-row">
            <div class="detail-label">出勤・退勤</div>
            <div class="detail-value">
                <div class="time-range">
                    <span class="request-time">{{ $attendanceRequest->requested_clock_in_at->format('H:i') }}</span>
                    <span class="time-separator">〜</span>
                    <span class="request-time">{{ $attendanceRequest->requested_clock_out_at->format('H:i') }}</span>
                </div>
            </div>
        </div>

        @foreach ($breakTimes as $index => $breakTime)
            <div class="detail-row">
                <div class="detail-label">休憩{{ $index === 0 ? '' : $index + 1 }}</div>
                <div class="detail-value">
                    <div class="time-range">
                        @if ($breakTime['start'] !== '' && $breakTime['end'] !== '')
                            <span class="request-time">{{ $breakTime['start'] }}</span>
                            <span class="time-separator">〜</span>
                            <span class="request-time">{{ $breakTime['end'] }}</span>
                        @else
                            <span class="request-time request-time-empty"></span>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach

        <div class="detail-row">
            <div class="detail-label">備考</div>
            <div class="detail-value">
                <span class="request-note">{{ $attendanceRequest->note }}</span>
            </div>
        </div>
    </div>

    @if ($attendanceRequest->isPending())
        <div class="button-row">
            <form method="POST" action="{{ route('admin.requests.approve', $attendanceRequest) }}">
                @csrf
                <button class="button" type="submit">承認</button>
            </form>
        </div>
    @else
        <div class="button-row">
            <button class="button button-muted" type="button" disabled>承認済み</button>
        </div>
    @endif
</div>
@endsection
