<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceDetailController extends Controller
{
    /**
     * Display the selected attendance detail for the authenticated user.
     */
    public function __invoke(Request $request, Attendance $attendance): View
    {
        abort_unless($attendance->user_id === $request->user()->id, 403);

        $attendance->load('user', 'breakTimes', 'requests');
        $pendingRequest = $attendance->pendingRequest();

        return view('attendance.show', [
            'attendance' => $attendance,
            'pendingRequest' => $pendingRequest,
            'formValues' => $this->formValues($attendance, $pendingRequest),
        ]);
    }

    /**
     * Store a correction request for the selected attendance.
     */
    public function store(StoreAttendanceCorrectionRequest $request, Attendance $attendance): RedirectResponse
    {
        abort_unless($attendance->user_id === $request->user()->id, 403);

        $attendance->load('requests');

        if ($attendance->pendingRequest()) {
            return redirect()->route('attendance.show', $attendance);
        }

        $workDate = $attendance->work_date->format('Y-m-d');

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $request->user()->id,
            'requested_clock_in_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $workDate.' '.$request->string('clock_in'), 'Asia/Tokyo'),
            'requested_clock_out_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $workDate.' '.$request->string('clock_out'), 'Asia/Tokyo'),
            'requested_break_times' => $request->normalizedBreakTimes(),
            'note' => (string) $request->string('note'),
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        return redirect()
            ->route('attendance.show', $attendance)
            ->with('message', '修正申請を送信しました。');
    }

    /**
     * @return array{clock_in: string, clock_out: string, break_times: array<int, array{start: string, end: string}>, note: string}
     */
    private function formValues(Attendance $attendance, ?AttendanceRequest $pendingRequest): array
    {
        if ($pendingRequest) {
            $breakTimes = array_values($pendingRequest->requested_break_times);
            $breakTimes[] = ['start' => '', 'end' => ''];

            return [
                'clock_in' => $pendingRequest->requested_clock_in_at->format('H:i'),
                'clock_out' => $pendingRequest->requested_clock_out_at->format('H:i'),
                'break_times' => $breakTimes,
                'note' => $pendingRequest->note,
            ];
        }

        $breakTimes = $attendance->breakTimes
            ->map(fn ($breakTime) => [
                'start' => $breakTime->started_at->format('H:i'),
                'end' => $breakTime->ended_at?->format('H:i') ?? '',
            ])
            ->values()
            ->all();

        $breakTimes[] = ['start' => '', 'end' => ''];

        return [
            'clock_in' => $attendance->formattedClockIn(),
            'clock_out' => $attendance->formattedClockOut(),
            'break_times' => $breakTimes,
            'note' => '',
        ];
    }
}
