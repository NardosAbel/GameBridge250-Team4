<?php

namespace App\Domain\Venues\Services;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketScan;
use App\Domain\Venues\Models\Venue;
use Illuminate\Support\Facades\DB;

class VenueAnalyticsService
{
    /**
     * Compute comprehensive venue analytics.
     */
    public function getAnalytics(Venue $venue): array
    {
        $eventIds = $venue->events()->pluck('id')->all();

        // 1. Total Attendance (unique checked-in tickets for venue events)
        $scansQuery = TicketScan::where('venue_id', $venue->id)
            ->orWhereIn('event_id', $eventIds);

        $totalScans = $scansQuery->count();
        $successfulScans = (clone $scansQuery)->where('status', 'valid')->count();

        // 2. Ticket Sales & Revenue
        $ordersQuery = TicketOrder::whereIn('event_id', $eventIds)
            ->where('status', 'completed');

        $totalRevenue = (float) $ordersQuery->sum('total_amount');
        $totalTicketsSold = Ticket::whereIn('event_id', $eventIds)
            ->whereIn('status', ['active', 'checked_in', 'used'])
            ->count();

        // 3. Repeat Attendance Rate
        $attendeeCounts = Ticket::whereIn('event_id', $eventIds)
            ->whereIn('status', ['checked_in', 'used'])
            ->select('user_id', DB::raw('count(*) as event_count'))
            ->groupBy('user_id')
            ->pluck('event_count');

        $totalUniqueAttendees = $attendeeCounts->count();
        $repeatAttendees = $attendeeCounts->filter(fn ($count) => $count > 1)->count();
        $repeatRate = $totalUniqueAttendees > 0
            ? round(($repeatAttendees / $totalUniqueAttendees) * 100, 1)
            : 0.0;

        // 4. Popular Event Hours (distribution of check-in times)
        $scannedTickets = Ticket::whereIn('event_id', $eventIds)
            ->whereNotNull('checked_in_at')
            ->get();

        $hourDistribution = [];
        for ($h = 0; $h < 24; $h++) {
            $hourDistribution[$h] = 0;
        }

        foreach ($scannedTickets as $t) {
            if ($t->checked_in_at) {
                $hour = (int) $t->checked_in_at->format('G');
                $hourDistribution[$hour]++;
            }
        }

        return [
            'venue_id' => $venue->id,
            'venue_name' => $venue->name,
            'total_events' => count($eventIds),
            'total_attendance' => $successfulScans,
            'total_tickets_sold' => $totalTicketsSold,
            'total_revenue' => $totalRevenue,
            'currency' => $venue->currency ?? 'ETB',
            'repeat_attendee_rate' => $repeatRate,
            'average_rating' => $venue->average_rating,
            'total_reviews' => $venue->reviews()->count(),
            'popular_hours' => $hourDistribution,
        ];
    }
}
