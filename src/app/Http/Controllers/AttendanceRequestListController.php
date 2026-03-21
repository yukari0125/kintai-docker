<?php

namespace App\Http\Controllers;

use App\Models\AttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceRequestListController extends Controller
{
    /**
     * Display attendance correction requests for the authenticated user.
     */
    public function __invoke(Request $request): View
    {
        $status = $request->query('status', AttendanceRequest::STATUS_PENDING);
        $allowedStatuses = [
            AttendanceRequest::STATUS_PENDING,
            AttendanceRequest::STATUS_APPROVED,
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = AttendanceRequest::STATUS_PENDING;
        }

        $requests = $request->user()
            ->attendances()
            ->with(['requests' => fn ($query) => $query->where('status', $status)->latest(), 'breakTimes'])
            ->get()
            ->pluck('requests')
            ->flatten()
            ->sortByDesc('created_at')
            ->values();

        return view('attendance.requests.index', [
            'status' => $status,
            'requests' => $requests,
        ]);
    }
}
