<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAttendanceRequestApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_pending_requests_list(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [],
            'note' => '管理者確認用申請',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.requests.index'));

        $response->assertOk();
        $response->assertSee('申請一覧');
        $response->assertSee('状態');
        $response->assertSee('名前');
        $response->assertSee('対象日');
        $response->assertSee('備考');
        $response->assertSee('申請日時');
        $response->assertSee('詳細');
        $response->assertSee('承認待ち');
        $response->assertSee('管理者確認用申請');
        $response->assertSee($user->name);
        $response->assertSee(route('admin.requests.show', AttendanceRequest::first()), false);
    }

    public function test_admin_can_view_approved_requests_tab(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [],
            'note' => '承認済み管理者確認',
            'status' => AttendanceRequest::STATUS_APPROVED,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.requests.index', ['status' => 'approved']));

        $response->assertOk();
        $response->assertSee('承認済み');
        $response->assertSee('承認済み管理者確認');
    }

    public function test_admin_can_open_request_detail_and_approve_it(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);
        $attendance->breakTimes()->create([
            'started_at' => Carbon::create(2026, 3, 12, 12, 0, 0, 'Asia/Tokyo'),
            'ended_at' => Carbon::create(2026, 3, 12, 13, 0, 0, 'Asia/Tokyo'),
        ]);

        $attendanceRequest = AttendanceRequest::create([
            'attendance_id' => $attendance->id,
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [
                ['start' => '12:15', 'end' => '13:00'],
                ['start' => '15:00', 'end' => '15:15'],
            ],
            'note' => '承認してください',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $detailResponse = $this->actingAs($admin)->get(route('admin.requests.show', $attendanceRequest));
        $detailResponse->assertOk();
        $detailResponse->assertSee('管理者申請詳細');
        $detailResponse->assertSee('名前');
        $detailResponse->assertSee('日付');
        $detailResponse->assertSee('出勤・退勤');
        $detailResponse->assertSee('休憩');
        $detailResponse->assertSee('休憩2');
        $detailResponse->assertSee('備考');
        $detailResponse->assertSee($user->name);
        $detailResponse->assertSee('2026年');
        $detailResponse->assertSee('3月12日');
        $detailResponse->assertSee('09:15');
        $detailResponse->assertSee('18:15');
        $detailResponse->assertSee('12:15');
        $detailResponse->assertSee('13:00');
        $detailResponse->assertSee('15:00');
        $detailResponse->assertSee('15:15');
        $detailResponse->assertSee('承認してください');
        $detailResponse->assertSee('承認');

        $approveResponse = $this->actingAs($admin)->post(route('admin.requests.approve', $attendanceRequest));
        $approveResponse->assertRedirect(route('admin.requests.show', $attendanceRequest));

        $attendance->refresh();
        $attendanceRequest->refresh();

        $this->assertSame(AttendanceRequest::STATUS_APPROVED, $attendanceRequest->status);
        $this->assertSame('09:15', $attendance->clock_in_at->format('H:i'));
        $this->assertSame('18:15', $attendance->clock_out_at->format('H:i'));
        $this->assertSame('承認してください', $attendance->note);
        $this->assertCount(2, $attendance->breakTimes()->get());
    }

    public function test_approved_request_moves_from_pending_tab_to_approved_tab_for_admin(): void
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
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [],
            'note' => '管理者承認移動確認',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $pendingResponse = $this->actingAs($admin)->get(route('admin.requests.index'));
        $pendingResponse->assertSee('管理者承認移動確認');

        $this->actingAs($admin)->post(route('admin.requests.approve', $attendanceRequest));

        $pendingResponse = $this->actingAs($admin)->get(route('admin.requests.index'));
        $pendingResponse->assertDontSee('管理者承認移動確認');

        $approvedResponse = $this->actingAs($admin)->get(route('admin.requests.index', ['status' => 'approved']));
        $approvedResponse->assertSee('管理者承認移動確認');
    }
}
