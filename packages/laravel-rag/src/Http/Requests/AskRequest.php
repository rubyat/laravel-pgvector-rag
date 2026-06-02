<?php

namespace Rubyat\LaravelRag\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AskRequest extends FormRequest
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
            'question' => ['required', 'string', 'max:2000'],
            'top_k' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
