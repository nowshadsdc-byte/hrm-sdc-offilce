<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        if (! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole('super-admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'lunch_start_time' => ['nullable', 'date_format:H:i'],
            'lunch_end_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'lunch_end_action' => ['nullable', 'in:keep,remove,use_as_check_out'],
            'lunch_start_action' => ['nullable', 'in:keep,remove,use_as_check_out'],
            'lunch_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'total_work_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'overtime_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'check_in_time.date_format' => 'Check in time must be a valid time.',
            'lunch_start_time.date_format' => 'Lunch start time must be a valid time.',
            'lunch_end_time.date_format' => 'Lunch end time must be a valid time.',
            'check_out_time.date_format' => 'Check out time must be a valid time.',
        ];
    }
}
