<?php

namespace App\Domain\Venues\Actions;

use App\Domain\Venues\Models\Venue;
use App\Domain\Venues\Models\VenueReview;
use App\Models\User;
use InvalidArgumentException;

class SubmitVenueReviewAction
{
    public function execute(User $user, Venue $venue, array $data): VenueReview
    {
        $rating = (int) $data['rating'];
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5 stars.');
        }

        return VenueReview::updateOrCreate([
            'venue_id' => $venue->id,
            'user_id' => $user->id,
        ], [
            'rating' => $rating,
            'comment' => $data['comment'] ?? null,
        ]);
    }
}
