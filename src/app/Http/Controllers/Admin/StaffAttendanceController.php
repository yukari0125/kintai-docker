<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class StaffAttendanceController extends Controller
{
    /**
     * Display the monthly attendance list for the selected staff member.
     */
    public function __invoke(User $user): View
    {
        abort_unless($user->isGeneralUser(), 404);

        [$month, $attendances] = $this->resolveAttendanceData($user, request());

        return view('admin.staff.attendance', [
            'staffUser' => $user,
            'month' => $month,
            'previousMonth' => $month->subMonth()->format('Y-m'),
            'nextMonth' => $month->addMonth()->format('Y-m'),
            'attendances' => $attendances,
        ]);
    }

    /**
     * Export the selected staff member's monthly attendances as CSV.
     */
    public function export(User $user, Request $request): StreamedResponse
    {
        abort_unless($user->isGeneralUser(), 404);

        [$month, $attendances] = $this->resolveAttendanceData($user, $request);
        $fileName = sprintf(
            'attendance_%s_%s.csv',
            $user->id,
            $month->format('Y_m')
        );

        return response()->streamDownload(function () use ($attendances): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($attendances as $attendance) {
                fputcsv($handle, [
                    $attendance->workDateLabel(),
                    $attendance->formattedClockIn(),
                    $attendance->formattedClockOut(),
                    $attendance->formattedBreakTotal(),
                    $attendance->formattedWorkDuration(),
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{0: CarbonImmutable, 1: \Illuminate\Support\Collection<int, Attendance>}
     */
    private function resolveAttendanceData(User $user, Request $request): array
    {
        $currentMonth = $request->query('month');
        $month = $currentMonth
            ? CarbonImmutable::createFromFormat('Y-m', $currentMonth, 'Asia/Tokyo')->startOfMonth()->locale('ja')
            : CarbonImmutable::now('Asia/Tokyo')->startOfMonth()->locale('ja');

        $attendances = $user->attendances()
            ->with('breakTimes')
            ->whereBetween('work_date', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->orderBy('work_date')
            ->get();

        return [$month, $attendances];
    }
}
