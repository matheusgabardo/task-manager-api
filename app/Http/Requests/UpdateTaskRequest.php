<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::enum(TaskStatus::class)],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if (! $this->hasAny(['status', 'priority'])) {
                    $validator->errors()->add('status', 'At least one of status or priority must be provided.');
                }
            },
        ];
    }
}
