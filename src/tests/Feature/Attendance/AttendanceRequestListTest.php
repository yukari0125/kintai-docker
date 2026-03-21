<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceRequestListTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_tab_shows_only_pending_requests_for_logged_in_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [],
            'note' => '自分の承認待ち申請',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $otherAttendance = Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-03-13',
            'clock_in_at' => Carbon::create(2026, 3, 13, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 13, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        AttendanceRequest::create([
            'attendance_id' => $otherAttendance->id,
            'user_id' => $otherUser->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 13, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 13, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [],
            'note' => '他人の承認待ち申請',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.requests.index'));

        $response->assertOk();
        $response->assertSee('承認待ち');
        $response->assertSee('自分の承認待ち申請');
        $response->assertDontSee('他人の承認待ち申請');
        $response->assertSee('状態');
        $response->assertSee('対象日');
        $response->assertSee('備考');
        $response->assertSee('申請日時');
        $response->assertSee('詳細');
    }

    public function test_approved_tab_shows_only_approved_requests(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [],
            'note' => '承認待ち申請',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 30, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 30, 0, 'Asia/Tokyo'),
            'requested_break_times' => [],
            'note' => '承認済み申請',
            'status' => AttendanceRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.requests.index', ['status' => 'approved']));

        $response->assertOk();
        $response->assertSee('承認済み申請');
        $response->assertDontSee('承認待ち申請');
    }

    public function test_request_list_has_detail_link_to_attendance_detail(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [],
            'note' => '申請詳細確認',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.requests.index'));

        $response->assertOk();
        $response->assertSee(route('attendance.show', $attendance), false);
    }

    public function test_request_moves_from_pending_tab_to_approved_tab_after_admin_approval(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $attendanceRequest = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [],
            'note' => '承認移動確認',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $pendingResponse = $this->actingAs($user)->get(route('attendance.requests.index'));
        $pendingResponse->assertSee('承認移動確認');

        $this->actingAs($admin)->post(route('admin.requests.approve', $attendanceRequest));

        $pendingResponse = $this->actingAs($user)->get(route('attendance.requests.index'));
        $pendingResponse->assertDontSee('承認移動確認');

        $approvedResponse = $this->actingAs($user)->get(route('attendance.requests.index', ['status' => 'approved']));
        $approvedResponse->assertSee('承認移動確認');
    }
}
