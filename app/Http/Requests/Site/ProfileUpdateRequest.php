<?php

namespace App\Http\Requests\Site;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
            'photo' => "nullable|image|max:6000|mimes:png,jpg,jpeg,svg",
            'name' => "required|string|min:1|max:20",
            'surname' => "required|string|min:1|max:20",
            'birth' => "required|date|date_format:Y-m-d|before:",
            'sex' => "required|integer|min:0|max:1",
        ];
    }
}
