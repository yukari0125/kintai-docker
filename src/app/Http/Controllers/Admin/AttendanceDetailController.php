<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAttendanceRequest;
use App\Models\Attendance;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AttendanceDetailController extends Controller
{
    /**
     * Display the selected attendance detail for admins.
     */
    public function __invoke(Attendance $attendance): View
    {
        $attendance->load('user', 'breakTimes');

        return view('admin.attendance.show', [
            'attendance' => $attendance,
            'formValues' => $this->formValues($attendance),
        ]);
    }

    /**
     * Update the selected attendance directly from the admin detail screen.
     */
    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $attendance->load('breakTimes');
        $workDate = $attendance->work_date->format('Y-m-d');

        DB::transaction(function () use ($attendance, $request, $workDate): void {
            $attendance->update([
                'clock_in_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $workDate.' '.$request->string('clock_in'), 'Asia/Tokyo'),
                'clock_out_at' => CarbonImmutable::createFromFormat('Y-m-d H:i', $workDate.' '.$request->string('clock_out'), 'Asia/Tokyo'),
                'note' => $request->filled('note') ? (string) $request->string('note') : null,
            ]);

            $attendance->breakTimes()->delete();

            foreach ($request->normalizedBreakTimes() as $breakTime) {
                $attendance->breakTimes()->create([
                    'started_at' => CarbonImmutable::createFromFormat(
                        'Y-m-d H:i',
                        $workDate.' '.$breakTime['start'],
                        'Asia/Tokyo'
                    ),
                    'ended_at' => CarbonImmutable::createFromFormat(
                        'Y-m-d H:i',
                        $workDate.' '.$breakTime['end'],
                        'Asia/Tokyo'
                    ),
                ]);
            }
        });

        return redirect()
            ->route('admin.attendance.show', $attendance)
            ->with('message', '勤怠情報を更新しました。');
    }

    /**
     * @return array{clock_in: string, clock_out: string, break_times: array<int, array{start: string, end: string}>, note: string}
     */
    private function formValues(Attendance $attendance): array
    {
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
            'note' => $attendance->note ?? '',
        ];
    }
}
