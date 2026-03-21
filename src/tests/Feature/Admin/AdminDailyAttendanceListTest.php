<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminDailyAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_daily_attendance_list_shows_all_general_users_for_current_date(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $userWithAttendance = User::factory()->create(['name' => '山田太郎']);
        $userWithoutAttendance = User::factory()->create(['name' => '佐藤花子']);

        $attendance = Attendance::create([
            'user_id' => $userWithAttendance->id,
            'work_date' => '2026-03-15',
            'clock_in_at' => Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 15, 18, 0, 0, 'Asia/Tokyo'),
        ]);
        $attendance->breakTimes()->create([
            'started_at' => Carbon::create(2026, 3, 15, 12, 0, 0, 'Asia/Tokyo'),
            'ended_at' => Carbon::create(2026, 3, 15, 13, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index'));

        $response->assertOk();
        $response->assertSee('2026年3月15日の勤怠');
        $response->assertSee('2026/03/15');
        $response->assertSee('名前');
        $response->assertSee('出勤');
        $response->assertSee('退勤');
        $response->assertSee('休憩');
        $response->assertSee('合計');
        $response->assertSee('詳細');
        $response->assertSee('山田太郎');
        $response->assertSee('佐藤花子');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
        $response->assertSee(route('admin.attendance.show', $attendance), false);
        $response->assertSee('<td></td>', false);
    }

    public function test_admin_daily_attendance_list_can_navigate_previous_and_next_date(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['name' => '山田太郎']);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-14',
            'clock_in_at' => Carbon::create(2026, 3, 14, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 14, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-15',
            'clock_in_at' => Carbon::create(2026, 3, 15, 10, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 15, 19, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.index', ['date' => '2026-03-14']));

        $response->assertOk();
        $response->assertSee('2026/03/14');
        $response->assertSee('09:00');
        $response->assertDontSee('10:00');
        $response->assertSee('href="'.route('admin.attendance.index', ['date' => '2026-03-13']).'"', false);
        $response->assertSee('href="'.route('admin.attendance.index', ['date' => '2026-03-15']).'"', false);
    }

    public function test_admin_can_open_daily_attendance_detail(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['name' => '山田太郎']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-15',
            'clock_in_at' => Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 15, 18, 0, 0, 'Asia/Tokyo'),
        ]);
        $attendance->breakTimes()->create([
            'started_at' => Carbon::create(2026, 3, 15, 12, 0, 0, 'Asia/Tokyo'),
            'ended_at' => Carbon::create(2026, 3, 15, 12, 30, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.attendance.show', $attendance));

        $response->assertOk();
        $response->assertSee('管理者勤怠詳細');
        $response->assertSee('山田太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('12:30');
        $response->assertSee('value="09:00"', false);
        $response->assertSee('value="18:00"', false);
        $response->assertSee('value="12:00"', false);
        $response->assertSee('value="12:30"', false);
        $response->assertSee('休憩2');
        $response->assertSee('textarea class="input textarea detail-note" name="note"', false);
        $response->assertSee('>修正</button>', false);
    }

    public function test_admin_can_update_attendance_detail_directly(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create(['name' => '山田太郎']);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-15',
            'clock_in_at' => Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 15, 18, 0, 0, 'Asia/Tokyo'),
        ]);
        $attendance->breakTimes()->create([
            'started_at' => Carbon::create(2026, 3, 15, 12, 0, 0, 'Asia/Tokyo'),
            'ended_at' => Carbon::create(2026, 3, 15, 12, 30, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.attendance.update', $attendance), [
            'clock_in' => '08:30',
            'clock_out' => '17:30',
            'break_starts' => ['12:00'],
            'break_ends' => ['13:00'],
            'note' => '管理者が修正しました',
        ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));

        $attendance->refresh();

        $this->assertSame('08:30', $attendance->formattedClockIn());
        $this->assertSame('17:30', $attendance->formattedClockOut());
        $this->assertSame('管理者が修正しました', $attendance->note);
        $this->assertSame('1:00', $attendance->fresh('breakTimes')->formattedBreakTotal());
        $this->assertSame('8:00', $attendance->fresh('breakTimes')->formattedWorkDuration());
    }

    public function test_admin_update_attendance_detail_validates_time_order(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-15',
            'clock_in_at' => Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 15, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->from(route('admin.attendance.show', $attendance))
            ->actingAs($admin)
            ->post(route('admin.attendance.update', $attendance), [
                'clock_in' => '18:00',
                'clock_out' => '09:00',
                'break_starts' => [],
                'break_ends' => [],
                'note' => '管理者メモ',
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors([
            'clock_in' => '出勤時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_admin_update_attendance_detail_validates_break_time_range(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-15',
            'clock_in_at' => Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 15, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->from(route('admin.attendance.show', $attendance))
            ->actingAs($admin)
            ->post(route('admin.attendance.update', $attendance), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_starts' => ['08:00'],
                'break_ends' => ['08:30'],
                'note' => '管理者メモ',
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors([
            'break_starts.0' => '休憩時間が不適切な値です',
        ]);
    }

    public function test_admin_update_attendance_detail_validates_break_end_against_clock_out(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-15',
            'clock_in_at' => Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 15, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->from(route('admin.attendance.show', $attendance))
            ->actingAs($admin)
            ->post(route('admin.attendance.update', $attendance), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_starts' => ['17:30'],
                'break_ends' => ['18:30'],
                'note' => '管理者メモ',
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors([
            'break_ends.0' => '休憩時間もしくは退勤時間が不適切な値です',
        ]);
    }

    public function test_admin_update_attendance_detail_requires_note(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-15',
            'clock_in_at' => Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 15, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->from(route('admin.attendance.show', $attendance))
            ->actingAs($admin)
            ->post(route('admin.attendance.update', $attendance), [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'break_starts' => [],
                'break_ends' => [],
                'note' => '',
            ]);

        $response->assertRedirect(route('admin.attendance.show', $attendance));
        $response->assertSessionHasErrors([
            'note' => '備考を記入してください',
        ]);
    }
}
