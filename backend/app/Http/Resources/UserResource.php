<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'locale' => $this->locale,
            'status' => $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status,
            'is_minor' => (bool) $this->is_minor,
            'two_factor_enabled' => (bool) $this->two_factor_enabled,
            'email_verified' => ! is_null($this->email_verified_at),
            'phone_verified' => ! is_null($this->phone_verified_at),
            'roles' => $this->roles->pluck('name'),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'profile' => new ProfileResource($this->whenLoaded('profile')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
