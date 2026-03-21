<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    private const STATUS_OFF = '勤務外';

    private const STATUS_WORKING = '出勤中';

    private const STATUS_BREAK = '休憩中';

    private const STATUS_DONE = '退勤済';

    /**
     * Display the general user attendance page.
     */
    public function __invoke(Request $request): View
    {
        $now = $this->now();
        $attendance = $this->todayAttendance($request);

        return view('attendance.index', [
            'user' => $request->user(),
            'now' => $now,
            'attendance' => $attendance,
            'status' => $this->status($attendance),
            'message' => session('message'),
        ]);
    }

    /**
     * Record clock-in for today.
     */
    public function clockIn(Request $request): RedirectResponse
    {
        $attendance = $this->todayAttendance($request);

        if ($attendance || $this->status($attendance) !== self::STATUS_OFF) {
            return redirect()->route('attendance.index');
        }

        $now = $this->now();

        Attendance::create([
            'user_id' => $request->user()->id,
            'work_date' => $now->toDateString(),
            'clock_in_at' => $now,
        ]);

        return redirect()->route('attendance.index');
    }

    /**
     * Record break start.
     */
    public function breakStart(Request $request): RedirectResponse
    {
        $attendance = $this->todayAttendance($request);

        if (! $attendance || $this->status($attendance) !== self::STATUS_WORKING) {
            return redirect()->route('attendance.index');
        }

        $attendance->breakTimes()->create([
            'started_at' => $this->now(),
        ]);

        return redirect()->route('attendance.index');
    }

    /**
     * Record break end.
     */
    public function breakEnd(Request $request): RedirectResponse
    {
        $attendance = $this->todayAttendance($request);

        if (! $attendance || $this->status($attendance) !== self::STATUS_BREAK) {
            return redirect()->route('attendance.index');
        }

        $attendance->breakTimes()
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first()
            ?->update([
                'ended_at' => $this->now(),
            ]);

        return redirect()->route('attendance.index');
    }

    /**
     * Record clock-out.
     */
    public function clockOut(Request $request): RedirectResponse
    {
        $attendance = $this->todayAttendance($request);

        if (! $attendance || $this->status($attendance) !== self::STATUS_WORKING) {
            return redirect()->route('attendance.index');
        }

        $attendance->update([
            'clock_out_at' => $this->now(),
        ]);

        return redirect()
            ->route('attendance.index')
            ->with('message', 'お疲れ様でした。');
    }

    private function todayAttendance(Request $request): ?Attendance
    {
        return $request->user()
            ->attendances()
            ->with('breakTimes')
            ->whereDate('work_date', $this->now()->toDateString())
            ->first();
    }

    private function status(?Attendance $attendance): string
    {
        if (! $attendance || ! $attendance->clock_in_at) {
            return self::STATUS_OFF;
        }

        if ($attendance->clock_out_at) {
            return self::STATUS_DONE;
        }

        if ($attendance->breakTimes->contains(fn ($breakTime) => $breakTime->ended_at === null)) {
            return self::STATUS_BREAK;
        }

        return self::STATUS_WORKING;
    }

    private function now(): CarbonImmutable
    {
        return CarbonImmutable::now('Asia/Tokyo')->locale('ja');
    }
}
