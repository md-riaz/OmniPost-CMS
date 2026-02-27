<?php

namespace App\Http\Requests\Workflow;

use Illuminate\Foundation\Http\FormRequest;

class AddPostCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment_text' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'exists:post_comments,id'],
        ];
    }
}
