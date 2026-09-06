<?php

namespace App\Http\Requests\Interview;

use App\Enums\ExperienceLevel;
use App\Enums\InterviewMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSessionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'position_id'      => ['nullable', 'integer', 'exists:positions,id'],
            'job_description'  => ['nullable', 'string', 'min:50', 'max:10000'],
            'mode'             => ['required', Rule::enum(InterviewMode::class)],
            'question_count'   => ['required', 'integer', 'min:3', 'max:15'],
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if (empty($this->position_id) && empty($this->job_description)) {
                $v->errors()->add('position_id', 'Provide either a position or a job description.');
            }
        });
    }
}
