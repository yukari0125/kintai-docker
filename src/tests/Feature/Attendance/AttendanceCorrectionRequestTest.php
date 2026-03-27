<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceCorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendance_detail_shows_selected_attendance_information_and_edit_fields(): void
    {
        $user = User::factory()->create(['name' => '山田太郎']);
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
        $attendance->breakTimes()->create([
            'started_at' => Carbon::create(2026, 3, 12, 15, 0, 0, 'Asia/Tokyo'),
            'ended_at' => Carbon::create(2026, 3, 12, 15, 15, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show', $attendance));

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('2026年');
        $response->assertSee('3月12日');
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
        $response->assertSee('value="15:00"', false);
        $response->assertSee('value="15:15"', false);
        $response->assertSee('休憩3');
        $response->assertSee('textarea class="input textarea detail-note" name="note"', false);
        $response->assertSee('>修正</button>', false);
    }

    public function test_correction_request_requires_note(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->post(route('attendance.request.store', $attendance), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_starts' => ['12:00'],
            'break_ends' => ['13:00'],
            'note' => '',
        ]);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }

    public function test_correction_request_validates_clock_in_and_clock_out_relationship(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->post(route('attendance.request.store', $attendance), [
            'clock_in' => '19:00',
            'clock_out' => '18:00',
            'break_starts' => [''],
            'break_ends' => [''],
            'note' => '打刻漏れのため修正',
        ]);

        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_correction_request_validates_break_time_range(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->post(route('attendance.request.store', $attendance), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_starts' => ['08:00'],
            'break_ends' => ['08:30'],
            'note' => '打刻漏れのため修正',
        ]);

        $response->assertSessionHasErrors([
            'break_starts.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_correction_request_validates_break_end_against_clock_out(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->post(route('attendance.request.store', $attendance), [
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'break_starts' => ['17:30'],
            'break_ends' => ['18:30'],
            'note' => '打刻漏れのため修正',
        ]);

        $response->assertSessionHasErrors([
            'break_ends.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_general_user_can_submit_correction_request(): void
    {
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->post(route('attendance.request.store', $attendance), [
            'clock_in' => '09:15',
            'clock_out' => '18:15',
            'break_starts' => ['12:00', ''],
            'break_ends' => ['13:00', ''],
            'note' => '打刻漏れのため修正',
        ]);

        $response->assertRedirect(route('attendance.show', $attendance));
        $this->assertDatabaseHas('attendance_requests', [
            'attendance_id' => $attendance->id,
            'status' => AttendanceRequest::STATUS_PENDING,
            'note' => '打刻漏れのため修正',
        ]);
    }

    public function test_submitted_correction_request_appears_in_user_and_admin_pending_lists(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $this->actingAs($user)->post(route('attendance.request.store', $attendance), [
            'clock_in' => '09:15',
            'clock_out' => '18:15',
            'break_starts' => ['12:00'],
            'break_ends' => ['13:00'],
            'note' => '一覧反映確認',
        ]);

        $userListResponse = $this->actingAs($user)->get(route('attendance.requests.index'));
        $userListResponse->assertOk();
        $userListResponse->assertSee('一覧反映確認');

        $adminListResponse = $this->actingAs($admin)->get(route('admin.requests.index'));
        $adminListResponse->assertOk();
        $adminListResponse->assertSee('一覧反映確認');
    }

    public function test_pending_request_disables_correction_form(): void
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
            'requested_clock_in_at' => Carbon::create(2026, 3, 12, 9, 15, 0, 'Asia/Tokyo'),
            'requested_clock_out_at' => Carbon::create(2026, 3, 12, 18, 15, 0, 'Asia/Tokyo'),
            'requested_break_times' => [['start' => '12:00', 'end' => '13:00']],
            'note' => '打刻漏れのため修正',
            'status' => AttendanceRequest::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show', $attendance));

        $response->assertOk();
        $response->assertSee('承認待ちのため修正はできません。');
        $response->assertDontSee('修正</button>', false);
        $response->assertSee('value="09:15"', false);
        $response->assertSee('value="18:15"', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="13:00"', false);
        $response->assertSee('休憩2');
        $response->assertSee('disabled', false);
    }
}
