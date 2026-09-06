<?php

namespace App\Http\Requests\Interview;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'question_id' => ['required', 'integer', 'exists:interview_questions,id'],
            'answer_text' => ['required', 'string', 'min:5', 'max:5000'],
        ];
    }
}
