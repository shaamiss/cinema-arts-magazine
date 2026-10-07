<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PublicationStatus;
use App\Models\Issue;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Issue::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'issue_number' => ['required', 'integer', 'min:1', 'unique:issues,issue_number'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:issues,slug'],
            'cover_image' => ['required', 'string', 'max:500'],
            'pdf_file_path' => ['required', 'string', 'max:500'],
            'published_at' => ['required', 'date'],
            'status' => ['nullable', Rule::enum(PublicationStatus::class)],
            'pages_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
