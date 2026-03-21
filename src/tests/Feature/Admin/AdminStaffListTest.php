<?php

namespace Tests\Feature\Admin;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminStaffListTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_staff_list_with_general_users_only(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN, 'name' => '管理者']);
        $staffA = User::factory()->create(['name' => '山田太郎', 'email' => 'yamada@example.com']);
        $staffB = User::factory()->create(['name' => '佐藤花子', 'email' => 'sato@example.com']);

        $response = $this->actingAs($admin)->get(route('admin.staff.index'));

        $response->assertOk();
        $response->assertSee('スタッフ一覧');
        $response->assertSee('名前');
        $response->assertSee('メールアドレス');
        $response->assertSee('月次勤怠');
        $response->assertSee('山田太郎');
        $response->assertSee('yamada@example.com');
        $response->assertSee('佐藤花子');
        $response->assertSee('sato@example.com');
        $response->assertDontSee('管理者');
        $response->assertSee('href="'.route('admin.staff.attendance', $staffA).'"', false);
        $response->assertSee('href="'.route('admin.staff.attendance', $staffB).'"', false);
    }

    public function test_admin_can_open_staff_monthly_attendance_list(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $staff = User::factory()->create(['name' => '山田太郎', 'email' => 'yamada@example.com']);
        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);
        $attendance->breakTimes()->create([
            'started_at' => Carbon::create(2026, 3, 12, 12, 0, 0, 'Asia/Tokyo'),
            'ended_at' => Carbon::create(2026, 3, 12, 13, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff.attendance', $staff));

        $response->assertOk();
        $response->assertSee('山田太郎');
        $response->assertSee('2026/03');
        $response->assertSee('03/12');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
        $response->assertSee(route('admin.attendance.show', $attendance), false);
        $response->assertSee('日付');
        $response->assertSee('出勤');
        $response->assertSee('退勤');
        $response->assertSee('休憩');
        $response->assertSee('合計');
        $response->assertSee('詳細');
        $response->assertSee('href="'.route('admin.staff.attendance', ['user' => $staff, 'month' => '2026-02']).'"', false);
        $response->assertSee('href="'.route('admin.staff.attendance', ['user' => $staff, 'month' => '2026-04']).'"', false);
        $response->assertSee('href="'.route('admin.staff.attendance.export', ['user' => $staff, 'month' => '2026-03']).'"', false);
        $response->assertDontSee('佐藤花子');
    }

    public function test_admin_staff_monthly_attendance_shows_blank_columns_for_missing_fields(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $staff = User::factory()->create(['name' => '山田太郎']);

        Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff.attendance', $staff));

        $response->assertOk();
        $response->assertSee('03/10');
        $response->assertSee('09:00');
        $response->assertSee('<td></td>', false);
    }

    public function test_admin_can_export_staff_monthly_attendance_as_csv(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $staff = User::factory()->create(['name' => '山田太郎', 'email' => 'yamada@example.com']);

        $attendance = Attendance::create([
            'user_id' => $staff->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);
        $attendance->breakTimes()->create([
            'started_at' => Carbon::create(2026, 3, 12, 12, 0, 0, 'Asia/Tokyo'),
            'ended_at' => Carbon::create(2026, 3, 12, 13, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff.attendance.export', [
            'user' => $staff,
            'month' => '2026-03',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('日付,出勤,退勤,休憩,合計', $content);
        $this->assertStringContainsString('03/12(木),09:00,18:00,1:00,8:00', $content);
    }
}
