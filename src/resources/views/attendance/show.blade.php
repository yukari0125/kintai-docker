@extends('layouts.app')

@section('content')
<div class="main-shell page-attendance-detail">
    <h1 class="page-heading"><span class="title">勤怠詳細</span></h1>

        @if (session('message'))
            <div class="success-message">{{ session('message') }}</div>
        @endif

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('attendance.request.store', $attendance) }}">
            @csrf

            <div class="detail-sheet">
                <div class="detail-row">
                    <div class="detail-label">名前</div>
                    <div class="detail-value">{{ $attendance->user->name }}</div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">日付</div>
                    <div class="detail-value">
                        <div class="date-split">
                            <span>{{ $attendance->work_date->format('Y年') }}</span>
                            <span>{{ $attendance->work_date->locale('ja')->isoFormat('M月D日') }}</span>
                        </div>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-label">出勤・退勤</div>
                    <div class="detail-value">
                        <div class="time-range">
                            <input class="input input-time" type="time" name="clock_in" value="{{ old('clock_in', $formValues['clock_in']) }}" @disabled($pendingRequest)>
                            <span class="time-separator">〜</span>
                            <input class="input input-time" type="time" name="clock_out" value="{{ old('clock_out', $formValues['clock_out']) }}" @disabled($pendingRequest)>
                        </div>
                    </div>
                </div>

                @foreach ($formValues['break_times'] as $index => $breakTime)
                    <div class="detail-row">
                        <div class="detail-label">休憩{{ $index === 0 ? '' : $index + 1 }}</div>
                        <div class="detail-value">
                            <div class="time-range">
                                <input class="input input-time" type="time" name="break_starts[]" value="{{ old("break_starts.$index", $breakTime['start']) }}" @disabled($pendingRequest)>
                                <span class="time-separator">〜</span>
                                <input class="input input-time" type="time" name="break_ends[]" value="{{ old("break_ends.$index", $breakTime['end']) }}" @disabled($pendingRequest)>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="detail-row">
                    <div class="detail-label">備考</div>
                    <div class="detail-value detail-value-textarea">
                        <textarea class="input textarea detail-note" name="note" @disabled($pendingRequest)>{{ old('note', $formValues['note']) }}</textarea>
                    </div>
                </div>
            </div>

            @if ($pendingRequest)
                <div class="pending-message">*承認待ちのため修正はできません。</div>
            @else
                <div class="button-row">
                    <button class="button" type="submit">修正</button>
                </div>
            @endif
        </form>
    </div>
@endsection
