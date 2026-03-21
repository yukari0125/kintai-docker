<?php

namespace App\Http\Requests\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],
            'break_starts' => ['array'],
            'break_starts.*' => ['nullable', 'date_format:H:i'],
            'break_ends' => ['array'],
            'break_ends.*' => ['nullable', 'date_format:H:i'],
            'note' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required' => '備考を記入してください',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $date = (string) $this->route('attendance')->work_date->format('Y-m-d');
            $clockIn = $this->toDateTime($date, $this->input('clock_in'));
            $clockOut = $this->toDateTime($date, $this->input('clock_out'));

            if (! $clockIn || ! $clockOut || $clockIn->gte($clockOut)) {
                $validator->errors()->add('clock_in', '出勤時間もしくは退勤時間が不適切な値です');

                return;
            }

            $breakStarts = $this->input('break_starts', []);
            $breakEnds = $this->input('break_ends', []);
            $count = max(count($breakStarts), count($breakEnds));

            for ($index = 0; $index < $count; $index++) {
                $breakStartRaw = $breakStarts[$index] ?? null;
                $breakEndRaw = $breakEnds[$index] ?? null;

                if (blank($breakStartRaw) && blank($breakEndRaw)) {
                    continue;
                }

                $breakStart = $this->toDateTime($date, $breakStartRaw);
                $breakEnd = $this->toDateTime($date, $breakEndRaw);

                if (! $breakStart || ! $breakEnd) {
                    $validator->errors()->add("break_starts.$index", '休憩時間が不適切な値です');

                    continue;
                }

                if ($breakStart->lt($clockIn) || $breakStart->gt($clockOut)) {
                    $validator->errors()->add("break_starts.$index", '休憩時間が不適切な値です');
                }

                if ($breakEnd->gt($clockOut) || $breakEnd->lte($breakStart)) {
                    $validator->errors()->add("break_ends.$index", '休憩時間もしくは退勤時間が不適切な値です');
                }
            }
        });
    }

    /**
     * @return array<int, array{start: string, end: string}>
     */
    public function normalizedBreakTimes(): array
    {
        $breakStarts = $this->input('break_starts', []);
        $breakEnds = $this->input('break_ends', []);
        $count = max(count($breakStarts), count($breakEnds));
        $breakTimes = [];

        for ($index = 0; $index < $count; $index++) {
            $breakStart = $breakStarts[$index] ?? null;
            $breakEnd = $breakEnds[$index] ?? null;

            if (blank($breakStart) && blank($breakEnd)) {
                continue;
            }

            $breakTimes[] = [
                'start' => $breakStart,
                'end' => $breakEnd,
            ];
        }

        return $breakTimes;
    }

    private function toDateTime(string $date, ?string $time): ?CarbonImmutable
    {
        if (blank($time)) {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d H:i', $date.' '.$time, 'Asia/Tokyo');
        } catch (\Throwable) {
            return null;
        }
    }
}
