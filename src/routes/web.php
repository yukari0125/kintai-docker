<?php

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\AttendanceDetailController as AdminAttendanceDetailController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\AttendanceRequestController as AdminAttendanceRequestController;
use App\Http\Controllers\Admin\StaffAttendanceController as AdminStaffAttendanceController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceDetailController;
use App\Http\Controllers\AttendanceListController;
use App\Http\Controllers\AttendanceRequestListController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

Route::redirect('/', '/login');

Route::middleware(['auth', 'general_user'])->get('/email/verify/link', function (Request $request) {
    $user = $request->user();

    if ($user->hasVerifiedEmail()) {
        return redirect()->route('attendance.index');
    }

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ],
    );

    return redirect()->to($verificationUrl);
})->name('verification.link');

Route::middleware('auth')->get('/stamp_correction_request/list', function (Request $request) {
    if ($request->user()?->isAdmin()) {
        return app(AdminAttendanceRequestController::class)->index($request);
    }

    if (! $request->user()?->hasVerifiedEmail()) {
        return redirect()->route('verification.notice');
    }

    return app(AttendanceRequestListController::class)($request);
})->name('stamp.requests.index');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{attendanceRequest}', [AdminAttendanceRequestController::class, 'show'])->name('stamp.requests.show');
    Route::post('/stamp_correction_request/approve/{attendanceRequest}', [AdminAttendanceRequestController::class, 'approve'])->name('stamp.requests.approve');
});

Route::middleware(['auth', 'verified', 'general_user'])->group(function () {
    Route::get('/attendance', AttendanceController::class)->name('attendance.index');
    Route::get('/attendance/list', AttendanceListController::class)->name('attendance.list');
    Route::get('/attendance/requests', AttendanceRequestListController::class)->name('attendance.requests.index');
    Route::get('/attendance/detail/{attendance}', AttendanceDetailController::class)->name('attendance.show');
    Route::post('/attendance/{attendance}/request', [AttendanceDetailController::class, 'store'])->name('attendance.request.store');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AdminAuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::get('/attendance/list', AdminAttendanceController::class)->name('attendance.index');
        Route::get('/attendance/staff/{user}', AdminStaffAttendanceController::class)->name('staff.attendance');
        Route::get('/attendance/staff/{user}/export', [AdminStaffAttendanceController::class, 'export'])->name('staff.attendance.export');
        Route::get('/attendance/{attendance}', AdminAttendanceDetailController::class)->name('attendance.show');
        Route::post('/attendance/{attendance}', [AdminAttendanceDetailController::class, 'update'])->name('attendance.update');
        Route::get('/staff/list', [AdminStaffController::class, 'index'])->name('staff.index');
        Route::get('/requests', [AdminAttendanceRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{attendanceRequest}', [AdminAttendanceRequestController::class, 'show'])->name('requests.show');
        Route::post('/requests/{attendanceRequest}/approve', [AdminAttendanceRequestController::class, 'approve'])->name('requests.approve');
        Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});
