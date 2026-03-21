<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    /**
     * Display the admin attendance page.
     */
    public function __invoke(Request $request): View
    {
        $currentDate = $request->query('date');
        $date = $currentDate
            ? CarbonImmutable::createFromFormat('Y-m-d', $currentDate, 'Asia/Tokyo')->locale('ja')
            : CarbonImmutable::now('Asia/Tokyo')->locale('ja');

        $users = User::query()
            ->where('role', User::ROLE_USER)
            ->with(['attendances' => function ($query) use ($date) {
                $query->with('breakTimes')
                    ->whereDate('work_date', $date->toDateString());
            }])
            ->orderBy('id')
            ->get()
            ->map(function (User $user) {
                $attendance = $user->attendances->first();

                return [
                    'user' => $user,
                    'attendance' => $attendance,
                ];
            });

        return view('admin.attendance.index', [
            'user' => $request->user(),
            'date' => $date,
            'previousDate' => $date->subDay()->format('Y-m-d'),
            'nextDate' => $date->addDay()->format('Y-m-d'),
            'users' => $users,
        ]);
    }
}
