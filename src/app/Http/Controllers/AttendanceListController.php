<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceListController extends Controller
{
    /**
     * Display the monthly attendance list for the authenticated user.
     */
    public function __invoke(Request $request): View
    {
        $currentMonth = $request->query('month');
        $month = $currentMonth
            ? CarbonImmutable::createFromFormat('Y-m', $currentMonth, 'Asia/Tokyo')->startOfMonth()->locale('ja')
            : CarbonImmutable::now('Asia/Tokyo')->startOfMonth()->locale('ja');

        $attendances = $request->user()
            ->attendances()
            ->with('breakTimes')
            ->whereBetween('work_date', [$month->toDateString(), $month->endOfMonth()->toDateString()])
            ->orderBy('work_date')
            ->get();

        return view('attendance.list', [
            'month' => $month,
            'previousMonth' => $month->subMonth()->format('Y-m'),
            'nextMonth' => $month->addMonth()->format('Y-m'),
            'attendances' => $attendances,
        ]);
    }
}
