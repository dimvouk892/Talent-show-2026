<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CorrectVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'score' => ['required', 'integer', 'min:1', 'max:10'],
            'reason' => ['required', 'string', 'min:5'],
        ];
    }
}
