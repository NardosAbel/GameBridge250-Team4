<?php

namespace App\Domain\Venues\Services;

use App\Domain\Venues\Models\Venue;
use Illuminate\Database\Eloquent\Collection;

class VenueDiscoveryService
{
    /**
     * Discover nearby venues using Haversine distance or city/facility filtering.
     *
     * @param  array<string>|null  $facilities
     */
    public function discover(
        ?float $latitude = null,
        ?float $longitude = null,
        float $radiusKm = 25.0,
        ?string $city = null,
        ?array $facilities = null,
        ?string $search = null
    ): Collection {
        $query = Venue::query()->with(['owner', 'reviews']);

        if ($search) {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($city) {
            $query->where('city', 'like', "%{$city}%");
        }

        if (! empty($facilities)) {
            foreach ($facilities as $facility) {
                $query->whereJsonContains('facilities', $facility);
            }
        }

        $venues = $query->get();

        // If coordinates provided, calculate Haversine distance and filter by radius
        if ($latitude !== null && $longitude !== null) {
            $venues = $venues->map(function ($venue) use ($latitude, $longitude) {
                if ($venue->latitude !== null && $venue->longitude !== null) {
                    $venue->distance_km = $this->calculateHaversineDistance(
                        $latitude,
                        $longitude,
                        $venue->latitude,
                        $venue->longitude
                    );
                } else {
                    $venue->distance_km = null;
                }

                return $venue;
            })->filter(function ($venue) use ($radiusKm) {
                return $venue->distance_km === null || $venue->distance_km <= $radiusKm;
            })->sortBy(function ($venue) {
                return $venue->distance_km ?? 999999;
            })->values();
        } else {
            // Sort verified and featured first
            $venues = $venues->sortByDesc('is_featured')
                ->sortByDesc('verified')
                ->values();
        }

        return $venues;
    }

    /**
     * Calculate Great Circle distance between two coordinates in kilometers.
     */
    protected function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadiusKm * $c, 2);
    }
}
