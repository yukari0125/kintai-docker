<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceRequestController extends Controller
{
    /**
     * Display attendance correction requests for admins.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', AttendanceRequest::STATUS_PENDING);
        $allowedStatuses = [
            AttendanceRequest::STATUS_PENDING,
            AttendanceRequest::STATUS_APPROVED,
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = AttendanceRequest::STATUS_PENDING;
        }

        $requests = AttendanceRequest::query()
            ->with(['attendance.user'])
            ->where('status', $status)
            ->latest()
            ->get();

        return view('admin.requests.index', [
            'status' => $status,
            'requests' => $requests,
        ]);
    }

    /**
     * Display a selected correction request.
     */
    public function show(AttendanceRequest $attendanceRequest): View
    {
        $attendanceRequest->load('attendance.user');

        return view('admin.requests.show', [
            'attendanceRequest' => $attendanceRequest,
        ]);
    }

    /**
     * Approve a correction request and apply it to the attendance.
     */
    public function approve(AttendanceRequest $attendanceRequest): RedirectResponse
    {
        if ($attendanceRequest->status === AttendanceRequest::STATUS_APPROVED) {
            return redirect()->route('admin.requests.show', $attendanceRequest);
        }

        $attendanceRequest->load('attendance.breakTimes');

        $attendanceRequest->attendance->update([
            'clock_in_at' => $attendanceRequest->requested_clock_in_at,
            'clock_out_at' => $attendanceRequest->requested_clock_out_at,
            'note' => $attendanceRequest->note,
        ]);

        $attendanceRequest->attendance->breakTimes()->delete();

        foreach ($attendanceRequest->requested_break_times as $breakTime) {
            $attendanceRequest->attendance->breakTimes()->create([
                'started_at' => CarbonImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $attendanceRequest->attendance->work_date->format('Y-m-d').' '.$breakTime['start'],
                    'Asia/Tokyo'
                ),
                'ended_at' => CarbonImmutable::createFromFormat(
                    'Y-m-d H:i',
                    $attendanceRequest->attendance->work_date->format('Y-m-d').' '.$breakTime['end'],
                    'Asia/Tokyo'
                ),
            ]);
        }

        $attendanceRequest->update([
            'status' => AttendanceRequest::STATUS_APPROVED,
        ]);

        return redirect()
            ->route('admin.requests.show', $attendanceRequest)
            ->with('message', '修正申請を承認しました。');
    }
}
