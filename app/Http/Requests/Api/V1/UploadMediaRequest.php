<?php

namespace App\Http\Requests\Api\V1;

use App\Models\MediaGallery;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('upload', MediaGallery::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:51200', 'mimes:jpeg,jpg,png,webp,mp3,wav,mp4,mov,pdf'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:media_galleries,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
