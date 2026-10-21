<?php

namespace App\Http\Requests\Moderation;

use Illuminate\Foundation\Http\FormRequest;

class TakeModerationActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->hasAnyRole(['super_admin', 'moderator']);
    }

    public function rules(): array
    {
        return [
            'target_user_id' => ['required', 'exists:users,id'],
            'action' => ['required', 'string', 'in:warning,mute,suspend,ban,delete_content,dismiss'],
            'reason' => ['required', 'string', 'max:1000'],
            'report_id' => ['nullable', 'exists:reports,id'],
            'content_id' => ['nullable', 'integer'],
            'content_type' => ['nullable', 'string', 'in:post,comment'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
