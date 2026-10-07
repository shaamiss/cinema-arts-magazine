<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PublicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateIssueRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $issue = $this->route('issue');

        return $this->user()?->can('update', $issue) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $issueId = $this->route('issue')?->id;

        return [
            'issue_number' => ['sometimes', 'required', 'integer', 'min:1', Rule::unique('issues', 'issue_number')->ignore($issueId)],
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('issues', 'slug')->ignore($issueId)],
            'cover_image' => ['sometimes', 'required', 'string', 'max:500'],
            'pdf_file_path' => ['sometimes', 'required', 'string', 'max:500'],
            'published_at' => ['sometimes', 'required', 'date'],
            'status' => ['nullable', Rule::enum(PublicationStatus::class)],
            'pages_count' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
