<?php

namespace App\Http\Requests\Auth;

use App\Enums\ExperienceLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'email'            => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'         => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
        ];
    }
}
