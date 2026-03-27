<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AttendanceRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'attendance_id',
        'requested_clock_in_at',
        'requested_clock_out_at',
        'requested_break_times',
        'note',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_clock_in_at' => 'datetime',
            'requested_clock_out_at' => 'datetime',
            'requested_break_times' => 'array',
        ];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function workDateLabel(): string
    {
        /** @var Carbon $workDate */
        $workDate = $this->attendance->work_date;

        return $workDate->locale('ja')->isoFormat('YYYY/MM/DD');
    }

    public function statusLabel(): string
    {
        return $this->status === self::STATUS_APPROVED ? '承認済み' : '承認待ち';
    }
}
