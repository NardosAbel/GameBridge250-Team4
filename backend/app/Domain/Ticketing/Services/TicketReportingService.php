<?php

namespace App\Domain\Ticketing\Services;

use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Ticketing\Models\TicketScan;

class TicketReportingService
{
    /**
     * Generate an aggregated event or tournament ticketing report.
     */
    public function generateReport(?int $eventId = null, ?int $tournamentId = null): array
    {
        $ticketsQuery = Ticket::query()->with('ticketType');
        $ordersQuery = TicketOrder::query();
        $scansQuery = TicketScan::query()->with('scanner');

        if ($eventId) {
            $ticketsQuery->where('event_id', $eventId);
            $ordersQuery->where('event_id', $eventId);
            $scansQuery->where('event_id', $eventId);
        }

        if ($tournamentId) {
            $ticketsQuery->where('tournament_id', $tournamentId);
            $ordersQuery->where('tournament_id', $tournamentId);
        }

        // 1. Sales & Revenue
        $completedOrders = (clone $ordersQuery)->whereIn('status', ['paid', 'completed'])->get();
        $totalRevenue = (float) $completedOrders->sum('total_amount');
        $totalSubtotal = (float) $completedOrders->sum('subtotal');
        $totalDiscounts = (float) $completedOrders->sum('discount_amount');
        $totalServiceFees = (float) $completedOrders->sum('service_fee');

        // 2. Attendance & No-Shows
        $totalTickets = (clone $ticketsQuery)->whereIn('status', ['active', 'checked_in', 'used'])->count();
        $attendedCount = (clone $ticketsQuery)->whereIn('status', ['checked_in', 'used'])->count();
        $noShows = max(0, $totalTickets - $attendedCount);
        $attendanceRate = $totalTickets > 0 ? round(($attendedCount / $totalTickets) * 100, 1) : 0.0;

        // 3. Refunds
        $refundedTickets = (clone $ticketsQuery)->where('status', 'refunded')->count();
        $refundedOrders = (clone $ordersQuery)->whereIn('status', ['refunded', 'partially_refunded'])->get();
        $refundAmount = (float) $refundedOrders->sum('total_amount');

        // 4. Ticket Categories Breakdown
        $categoryBreakdown = (clone $ticketsQuery)
            ->select('ticket_type_id')
            ->selectRaw('count(*) as count')
            ->selectRaw("sum(case when status in ('checked_in', 'used') then 1 else 0 end) as checked_in")
            ->groupBy('ticket_type_id')
            ->get()
            ->map(function ($row) {
                return [
                    'ticket_type_id' => $row->ticket_type_id,
                    'name' => $row->ticketType?->name ?? 'Unknown',
                    'category' => $row->ticketType?->category ?? 'general_admission',
                    'sold_count' => (int) $row->count,
                    'checked_in_count' => (int) $row->checked_in,
                ];
            })->all();

        // 5. Scanner Performance
        $scannerStats = (clone $scansQuery)
            ->select('scanner_user_id')
            ->selectRaw('count(*) as total_scans')
            ->selectRaw("sum(case when status = 'valid' then 1 else 0 end) as valid_scans")
            ->selectRaw("sum(case when status = 'duplicate' then 1 else 0 end) as duplicate_scans")
            ->groupBy('scanner_user_id')
            ->get()
            ->map(function ($row) {
                return [
                    'scanner_id' => $row->scanner_user_id,
                    'scanner_name' => $row->scanner?->name ?? 'Unknown Staff',
                    'total_scans' => (int) $row->total_scans,
                    'valid_scans' => (int) $row->valid_scans,
                    'duplicate_scans' => (int) $row->duplicate_scans,
                ];
            })->all();

        return [
            'event_id' => $eventId,
            'tournament_id' => $tournamentId,
            'sales' => [
                'total_orders' => $completedOrders->count(),
                'total_tickets_issued' => $totalTickets,
                'gross_revenue' => $totalSubtotal,
                'total_discounts' => $totalDiscounts,
                'service_fees' => $totalServiceFees,
                'net_revenue' => $totalRevenue,
                'currency' => 'ETB',
            ],
            'attendance' => [
                'total_issued' => $totalTickets,
                'attended' => $attendedCount,
                'no_shows' => $noShows,
                'attendance_rate' => $attendanceRate,
            ],
            'refunds' => [
                'refunded_tickets' => $refundedTickets,
                'refund_amount' => $refundAmount,
            ],
            'categories' => $categoryBreakdown,
            'scanner_performance' => $scannerStats,
        ];
    }

    /**
     * Export attendance data as CSV string.
     */
    public function exportCsv(?int $eventId = null, ?int $tournamentId = null): string
    {
        $ticketsQuery = Ticket::query()->with(['user', 'ticketType', 'order']);
        if ($eventId) {
            $ticketsQuery->where('event_id', $eventId);
        }
        if ($tournamentId) {
            $ticketsQuery->where('tournament_id', $tournamentId);
        }

        $tickets = $ticketsQuery->get();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Ticket Code', 'Attendee Name', 'Email', 'Category', 'Status', 'Checked In At', 'Order ID']);

        foreach ($tickets as $ticket) {
            fputcsv($output, [
                $ticket->ticket_code,
                $ticket->user?->name ?? 'Guest',
                $ticket->user?->email ?? 'N/A',
                $ticket->ticketType?->category ?? 'GA',
                $ticket->status,
                $ticket->checked_in_at?->toDateTimeString() ?? 'Not Checked In',
                $ticket->order?->uuid ?? 'N/A',
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent ?: '';
    }
}
