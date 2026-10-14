<?php

namespace App\Http\Requests\Community;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $community = $this->route('community');
        $communityId = is_object($community) ? $community->id : $community;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100', "unique:communities,name,{$communityId}"],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', 'string', 'in:school,game,city,venue,interest,creator'],
            'visibility' => ['nullable', 'string', 'in:public,private,approval'],
            'avatar_url' => ['nullable', 'string', 'url', 'max:255'],
            'banner_url' => ['nullable', 'string', 'url', 'max:255'],
        ];
    }
}
