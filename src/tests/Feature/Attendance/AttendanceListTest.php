<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_monthly_attendance_list_shows_only_logged_in_user_records(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

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

        Attendance::create([
            'user_id' => $otherUser->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 8, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 17, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertOk();
        $response->assertSee('2026/03');
        $response->assertSee('03/12');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('1:00');
        $response->assertSee('8:00');
        $response->assertSee(route('attendance.show', $attendance), false);
        $response->assertDontSee('08:00');
        $response->assertDontSee('17:00');
    }

    public function test_monthly_attendance_list_shows_blank_columns_for_missing_fields(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-10',
            'clock_in_at' => Carbon::create(2026, 3, 10, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertOk();
        $response->assertSee('03/10');
        $response->assertSee('09:00');
        $response->assertSee('<td></td>', false);
    }

    public function test_month_navigation_filters_target_month(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create();

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-02-20',
            'clock_in_at' => Carbon::create(2026, 2, 20, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 2, 20, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-20',
            'clock_in_at' => Carbon::create(2026, 3, 20, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 20, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list?month=2026-02');

        $response->assertOk();
        $response->assertSee('2026/02');
        $response->assertSee('02/20');
        $response->assertDontSee('03/20');
        $response->assertSee('href="'.route('attendance.list', ['month' => '2026-01']).'"', false);
        $response->assertSee('href="'.route('attendance.list', ['month' => '2026-03']).'"', false);
    }

    public function test_detail_link_opens_selected_attendance(): void
    {
        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);
        $attendance->breakTimes()->create([
            'started_at' => Carbon::create(2026, 3, 12, 12, 0, 0, 'Asia/Tokyo'),
            'ended_at' => Carbon::create(2026, 3, 12, 12, 30, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->get(route('attendance.show', $attendance));

        $response->assertOk();
        $response->assertSee('勤怠詳細');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('12:00');
        $response->assertSee('12:30');
    }

    public function test_monthly_attendance_list_contains_detail_link_for_each_record(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 15, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create();

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'work_date' => '2026-03-12',
            'clock_in_at' => Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'),
            'clock_out_at' => Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'),
        ]);

        $response = $this->actingAs($user)->get('/attendance/list');

        $response->assertOk();
        $response->assertSee('詳細');
        $response->assertSee('href="'.route('attendance.show', $attendance).'"', false);
    }
}
