<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class PostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'text' => 'required|max:255',
            'created_by' => 'required|integer|max:255',
            'location' => 'required|max:255',
            'photos' => 'nullable|array',
            'photos.*' => 'nullable|image|mimes:jpg,png,svg',
        ];
    }
}
