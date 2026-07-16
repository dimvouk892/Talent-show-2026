<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CorrectVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', Rule::in(config('talent-show.allowed_scores', [9, 10, 12]))],
            'reason' => ['required', 'string', 'min:5'],
        ];
    }
}
