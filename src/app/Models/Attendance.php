<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in_at',
        'clock_out_at',
        'note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'work_date' => 'date',
            'clock_in_at' => 'datetime',
            'clock_out_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes(): HasMany
    {
        return $this->hasMany(BreakTime::class);
    }

    public function activeBreak(): HasMany
    {
        return $this->breakTimes()->whereNull('ended_at');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function totalBreakMinutes(): int
    {
        return (int) $this->breakTimes->sum(function (BreakTime $breakTime): int {
            if (! $breakTime->started_at || ! $breakTime->ended_at) {
                return 0;
            }

            return $breakTime->started_at->diffInMinutes($breakTime->ended_at);
        });
    }

    public function formattedClockIn(): string
    {
        return $this->clock_in_at?->format('H:i') ?? '';
    }

    public function formattedClockOut(): string
    {
        return $this->clock_out_at?->format('H:i') ?? '';
    }

    public function formattedBreakTotal(): string
    {
        $minutes = $this->totalBreakMinutes();

        if ($minutes === 0) {
            return '';
        }

        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function formattedWorkDuration(): string
    {
        if (! $this->clock_in_at || ! $this->clock_out_at) {
            return '';
        }

        $minutes = $this->clock_in_at->diffInMinutes($this->clock_out_at) - $this->totalBreakMinutes();
        $minutes = max($minutes, 0);

        return sprintf('%d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function workDateLabel(): string
    {
        /** @var Carbon $workDate */
        $workDate = $this->work_date;

        return $workDate->locale('ja')->isoFormat('MM/DD(ddd)');
    }

    public function pendingRequest(): ?AttendanceRequest
    {
        return $this->requests->firstWhere('status', AttendanceRequest::STATUS_PENDING);
    }
}
