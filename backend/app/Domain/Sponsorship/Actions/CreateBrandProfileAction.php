<?php

namespace App\Domain\Sponsorship\Actions;

use App\Domain\Sponsorship\Models\BrandProfile;
use App\Models\User;

class CreateBrandProfileAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(User $user, array $data): BrandProfile
    {
        return BrandProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $data['company_name'],
                'tagline' => $data['tagline'] ?? null,
                'description' => $data['description'] ?? null,
                'industry' => $data['industry'] ?? 'gaming_gear',
                'logo_url' => $data['logo_url'] ?? null,
                'banner_url' => $data['banner_url'] ?? null,
                'website_url' => $data['website_url'] ?? null,
                'contact_email' => $data['contact_email'] ?? $user->email,
                'contact_phone' => $data['contact_phone'] ?? $user->phone,
                'social_links' => $data['social_links'] ?? [],
                'verified' => $data['verified'] ?? false,
            ]
        );
    }
}
