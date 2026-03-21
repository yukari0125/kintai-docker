<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'role' => User::ROLE_ADMIN,
            'password' => 'password123',
        ]);

        $generalUsers = collect([
            User::factory()->create([
                'name' => '一般ユーザー1',
                'email' => 'user1@example.com',
                'role' => User::ROLE_USER,
                'password' => 'password123',
            ]),
            User::factory()->create([
                'name' => '一般ユーザー2',
                'email' => 'user2@example.com',
                'role' => User::ROLE_USER,
                'password' => 'password123',
            ]),
        ]);

        $generalUsers->each(function (User $user, int $index): void {
            $this->seedAttendancesForUser($user, $index);
        });
    }

    private function seedAttendancesForUser(User $user, int $offsetDays): void
    {
        $baseDate = Carbon::create(2026, 3, 16)->subDays($offsetDays);

        for ($day = 0; $day < 5; $day++) {
            $workDate = $baseDate->copy()->subDays($day);
            $clockInAt = $workDate->copy()->setTime(9, 0);
            $clockOutAt = $workDate->copy()->setTime(18, 0);

            $attendance = Attendance::create([
                'user_id' => $user->id,
                'work_date' => $workDate->toDateString(),
                'clock_in_at' => $clockInAt,
                'clock_out_at' => $clockOutAt,
                'note' => '出社勤務',
            ]);

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'started_at' => $workDate->copy()->setTime(12, 0),
                'ended_at' => $workDate->copy()->setTime(13, 0),
            ]);
        }
    }
}
