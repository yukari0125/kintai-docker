@extends('layouts.app')

@section('content')
<div class="main-shell page-admin-attendance-list">
    <h1 class="page-heading"><span class="title">{{ $date->isoFormat('Y年M月D日') }}の勤怠</span></h1>

        <div class="month-nav">
            <a class="month-link" href="{{ route('admin.attendance.index', ['date' => $previousDate]) }}">← 前日</a>
            <div class="month-label">
                <span class="month-icon" aria-hidden="true">
                    <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="2.25" y="3.75" width="15.5" height="14" rx="1.75" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M5.5 2.5V5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M14.5 2.5V5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M2.75 7.25H17.25" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <rect x="5.25" y="9.5" width="2" height="2" rx=".4" fill="currentColor"/>
                        <rect x="9" y="9.5" width="2" height="2" rx=".4" fill="currentColor"/>
                        <rect x="12.75" y="9.5" width="2" height="2" rx=".4" fill="currentColor"/>
                        <rect x="5.25" y="13" width="2" height="2" rx=".4" fill="currentColor"/>
                        <rect x="9" y="13" width="2" height="2" rx=".4" fill="currentColor"/>
                    </svg>
                </span>
                <span class="month-current-date">{{ $date->isoFormat('YYYY/MM/DD') }}</span>
            </div>
            <a class="month-link" href="{{ route('admin.attendance.index', ['date' => $nextDate]) }}">翌日 →</a>
        </div>

        <div class="table-wrap">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $item)
                        <tr>
                            <td>{{ $item['user']->name }}</td>
                            <td>{{ $item['attendance']?->formattedClockIn() ?? '' }}</td>
                            <td>{{ $item['attendance']?->formattedClockOut() ?? '' }}</td>
                            <td>{{ $item['attendance']?->formattedBreakTotal() ?? '' }}</td>
                            <td>{{ $item['attendance']?->formattedWorkDuration() ?? '' }}</td>
                            <td>
                                @if ($item['attendance'])
                                    <a class="table-link" href="{{ route('admin.attendance.show', $item['attendance']) }}">詳細</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">一般ユーザーがいません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
</div>
@endsection
