<?php

namespace RagStarter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngestRequest extends FormRequest
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
            'source' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
