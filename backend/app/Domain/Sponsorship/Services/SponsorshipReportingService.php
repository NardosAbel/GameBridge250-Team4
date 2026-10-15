<?php

namespace App\Domain\Sponsorship\Services;

use App\Domain\Sponsorship\Models\Sponsorship;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentRegistration;

class SponsorshipReportingService
{
    /**
     * @return array<string, mixed>
     */
    public function generateReport(Sponsorship $sponsorship): array
    {
        $package = $sponsorship->package;
        $brand = $sponsorship->brandProfile;

        // Deliverables fulfillment
        $totalDeliverables = $sponsorship->deliverables()->count();
        $verifiedDeliverables = $sponsorship->deliverables()->where('status', 'verified')->count();
        $fulfillmentRate = $totalDeliverables > 0 ? (int) round(($verifiedDeliverables / $totalDeliverables) * 100) : 100;

        // Placements performance
        $placements = $sponsorship->placements;
        $impressions = $sponsorship->total_impressions + $placements->sum('impressions_count');
        $clicks = $sponsorship->total_clicks + $placements->sum('clicks_count');
        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0;

        // Target event metrics if applicable
        $eventMetrics = [];
        if ($sponsorship->target_type->value === 'tournament' && $sponsorship->target_id) {
            $tournament = Tournament::find($sponsorship->target_id);
            if ($tournament) {
                $registrationsCount = TournamentRegistration::where('tournament_id', $tournament->id)->count();
                $ticketCount = Ticket::where('tournament_id', $tournament->id)->count();
                $checkedInCount = Ticket::where('tournament_id', $tournament->id)->where('status', 'checked_in')->count();

                $eventMetrics = [
                    'tournament_title' => $tournament->title,
                    'participants_count' => $registrationsCount,
                    'tickets_sold' => $ticketCount,
                    'attendees_checked_in' => $checkedInCount,
                ];
            }
        }

        // Financial & ROI
        $cost = (float) $sponsorship->amount;
        $cpm = $impressions > 0 ? round(($cost / $impressions) * 1000, 2) : 0.0;
        $cpc = $clicks > 0 ? round($cost / $clicks, 2) : 0.0;
        $estimatedMediaValue = round($impressions * 0.15 + $clicks * 1.50, 2);

        $metrics = [
            'sponsorship_id' => $sponsorship->id,
            'contract_number' => $sponsorship->contract_number,
            'brand_name' => $brand ? $brand->company_name : ($sponsorship->sponsor?->name ?? 'Sponsor'),
            'package_title' => $package?->title ?? $sponsorship->package_name,
            'investment_amount' => $cost,
            'currency' => $sponsorship->currency,
            'status' => $sponsorship->status->value,
            'total_impressions' => $impressions,
            'total_clicks' => $clicks,
            'click_through_rate' => $ctr,
            'cpm' => $cpm,
            'cpc' => $cpc,
            'estimated_media_value' => $estimatedMediaValue,
            'deliverables' => [
                'total' => $totalDeliverables,
                'verified' => $verifiedDeliverables,
                'fulfillment_rate' => $fulfillmentRate,
            ],
            'event_metrics' => $eventMetrics,
            'generated_at' => now()->toIso8601String(),
        ];

        // Cache into sponsorship model
        $sponsorship->update(['roi_metrics' => $metrics]);

        return $metrics;
    }

    public function generateCsvReport(Sponsorship $sponsorship): string
    {
        $report = $this->generateReport($sponsorship);

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Contract Number', $report['contract_number']]);
        fputcsv($output, ['Brand Name', $report['brand_name']]);
        fputcsv($output, ['Package', $report['package_title']]);
        fputcsv($output, ['Investment Amount', "{$report['investment_amount']} {$report['currency']}"]);
        fputcsv($output, ['Total Impressions', $report['total_impressions']]);
        fputcsv($output, ['Total Clicks', $report['total_clicks']]);
        fputcsv($output, ['Click Through Rate (%)', $report['click_through_rate']]);
        fputcsv($output, ['Cost Per Mille (CPM)', "{$report['cpm']} {$report['currency']}"]);
        fputcsv($output, ['Cost Per Click (CPC)', "{$report['cpc']} {$report['currency']}"]);
        fputcsv($output, ['Estimated Media Value', "{$report['estimated_media_value']} {$report['currency']}"]);
        fputcsv($output, ['Deliverables Fulfillment Rate (%)', $report['deliverables']['fulfillment_rate']]);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }
}
