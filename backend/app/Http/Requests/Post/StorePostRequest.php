<?php

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:5000'],
            'title' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:text,clip,livestream,announcement,poll'],
            'visibility' => ['nullable', 'string', 'in:public,community,team'],
            'game_id' => ['nullable', 'exists:games,id'],
            'community_id' => ['nullable', 'exists:communities,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'location_tag' => ['nullable', 'string', 'max:100'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:50'],
            'poll_options' => ['nullable', 'array', 'min:2', 'max:10'],
            'poll_options.*' => ['string', 'max:100'],
            'media' => ['nullable', 'array', 'max:5'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,webm,mov', 'max:51200'], // 50MB max
            'pinned' => ['nullable', 'boolean'],
        ];
    }
}
