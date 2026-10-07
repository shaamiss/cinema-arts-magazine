<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\PublicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $article = $this->route('article');

        return $this->user()?->can('update', $article) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $articleId = $this->route('article')?->id;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('articles', 'slug')->ignore($articleId)],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            'content' => ['sometimes', 'required', 'string'],
            'featured_image' => ['nullable', 'string', 'max:500'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'issue_id' => ['nullable', 'integer', 'exists:issues,id'],
            'artist_profile_id' => ['nullable', 'integer', 'exists:artist_profiles,id'],
            'status' => ['nullable', Rule::enum(PublicationStatus::class)],
        ];
    }
}
