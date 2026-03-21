@extends('layouts.app')

@section('content')
<div class="main-shell page-admin-staff-attendance">
    <h1 class="page-heading"><span class="title">{{ $staffUser->name }}さんの勤怠</span></h1>

        <div class="month-nav">
            <a class="month-link" href="{{ route('admin.staff.attendance', ['user' => $staffUser, 'month' => $previousMonth]) }}">← 前月</a>
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
                <span class="month-current-date">{{ $month->isoFormat('YYYY/MM') }}</span>
            </div>
            <a class="month-link" href="{{ route('admin.staff.attendance', ['user' => $staffUser, 'month' => $nextMonth]) }}">翌月 →</a>
        </div>

        <div class="table-wrap">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        <tr>
                            <td class="table-date">{{ $attendance->workDateLabel() }}</td>
                            <td>{{ $attendance->formattedClockIn() }}</td>
                            <td>{{ $attendance->formattedClockOut() }}</td>
                            <td>{{ $attendance->formattedBreakTotal() }}</td>
                            <td>{{ $attendance->formattedWorkDuration() }}</td>
                            <td><a class="table-link" href="{{ route('admin.attendance.show', $attendance) }}">詳細</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-row">この月の勤怠情報はありません</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="button-row">
            <a class="button" href="{{ route('admin.staff.attendance.export', ['user' => $staffUser, 'month' => $month->format('Y-m')]) }}">CSV出力</a>
        </div>
</div>
@endsection
