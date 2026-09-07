<?php

namespace App\Http\Requests\Profile;

use App\Enums\ExperienceLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
        ];
    }
}
