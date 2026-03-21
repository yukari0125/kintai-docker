<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClockInAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_attendance_page_shows_off_status_initially(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 12, 8, 59, 0, 'Asia/Tokyo'));

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertOk();
        $response->assertSee('勤務外');
        $response->assertSee('2026年3月12日(木)');
        $response->assertSee('08:59');
        $response->assertSee('出勤');
        $response->assertSee('action="'.route('attendance.clock-in').'"', false);
        $response->assertDontSee('action="'.route('attendance.break-start').'"', false);
        $response->assertDontSee('action="'.route('attendance.break-end').'"', false);
        $response->assertDontSee('action="'.route('attendance.clock-out').'"', false);
    }

    public function test_user_can_clock_in_once_per_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create();

        $this->actingAs($user)->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        $attendance = Attendance::first();

        $this->assertNotNull($attendance);
        $this->assertSame($user->id, $attendance->user_id);
        $this->assertSame('2026-03-12', $attendance->work_date->toDateString());

        $this->actingAs($user)->post('/attendance/clock-in')
            ->assertRedirect('/attendance');

        $this->assertSame(1, Attendance::count());

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');
        $response->assertSee('退勤');
        $response->assertDontSee('action="'.route('attendance.clock-in').'"', false);
        $response->assertSee('action="'.route('attendance.break-start').'"', false);
        $response->assertSee('action="'.route('attendance.clock-out').'"', false);
    }

    public function test_user_can_start_and_end_breaks_multiple_times(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create();
        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2026, 3, 12, 12, 0, 0, 'Asia/Tokyo'));
        $this->actingAs($user)->post('/attendance/break-start');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩中');
        $response->assertSee('休憩戻');
        $response->assertDontSee('action="'.route('attendance.break-start').'"', false);
        $response->assertSee('action="'.route('attendance.break-end').'"', false);
        $response->assertDontSee('action="'.route('attendance.clock-out').'"', false);

        Carbon::setTestNow(Carbon::create(2026, 3, 12, 12, 30, 0, 'Asia/Tokyo'));
        $this->actingAs($user)->post('/attendance/break-end');

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('出勤中');
        $response->assertSee('休憩入');
        $response->assertSee('退勤');
        $response->assertSee('action="'.route('attendance.break-start').'"', false);
        $response->assertSee('action="'.route('attendance.clock-out').'"', false);

        Carbon::setTestNow(Carbon::create(2026, 3, 12, 15, 0, 0, 'Asia/Tokyo'));
        $this->actingAs($user)->post('/attendance/break-start');
        Carbon::setTestNow(Carbon::create(2026, 3, 12, 15, 15, 0, 'Asia/Tokyo'));
        $this->actingAs($user)->post('/attendance/break-end');

        $attendance = Attendance::with('breakTimes')->first();

        $this->assertCount(2, $attendance->breakTimes);
        $this->assertTrue($attendance->breakTimes->every(fn ($breakTime) => $breakTime->ended_at !== null));
    }

    public function test_user_can_clock_out_from_working_status(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create();
        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'));

        $response = $this->actingAs($user)->post('/attendance/clock-out');

        $response->assertRedirect('/attendance');
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('退勤済');
        $response->assertSee('お疲れ様でした。');
        $response->assertDontSee('action="'.route('attendance.clock-in').'"', false);
        $response->assertDontSee('action="'.route('attendance.break-start').'"', false);
        $response->assertDontSee('action="'.route('attendance.break-end').'"', false);
        $response->assertDontSee('action="'.route('attendance.clock-out').'"', false);

        $attendance = Attendance::first();

        $this->assertNotNull($attendance);
        $this->assertSame($user->id, $attendance->user_id);
        $this->assertSame('2026-03-12', $attendance->work_date->toDateString());
        $this->assertNotNull($attendance->clock_out_at);
    }

    public function test_user_cannot_clock_out_twice_per_day(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 12, 9, 0, 0, 'Asia/Tokyo'));

        $user = User::factory()->create();
        $this->actingAs($user)->post('/attendance/clock-in');

        Carbon::setTestNow(Carbon::create(2026, 3, 12, 18, 0, 0, 'Asia/Tokyo'));
        $this->actingAs($user)->post('/attendance/clock-out')
            ->assertRedirect('/attendance');

        Carbon::setTestNow(Carbon::create(2026, 3, 12, 18, 5, 0, 'Asia/Tokyo'));
        $this->actingAs($user)->post('/attendance/clock-out')
            ->assertRedirect('/attendance');

        $attendance = Attendance::first();

        $this->assertSame('18:00', $attendance?->formattedClockOut());
    }
}
