<?php

namespace App\Domain\Administration\Services;

use App\Domain\Education\Models\Certificate;
use App\Domain\Education\Models\Course;
use App\Domain\Education\Models\CourseEnrollment;
use App\Domain\Profiles\Models\Profile;
use App\Domain\Sponsorship\Models\Sponsorship;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Tournaments\Models\Dispute;
use App\Domain\Tournaments\Models\Tournament;
use App\Domain\Tournaments\Models\TournamentMatch;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformAnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public function getExecutiveMetrics(): array
    {
        // 1. Users & Retention
        $totalUsers = User::count();
        $verifiedStudents = Profile::where('student_verified', true)->count();
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $thirtyDaysAgo = Carbon::now()->subDays(30);

        $activeUsers7d = User::where('updated_at', '>=', $sevenDaysAgo)->count();
        $activeUsers30d = User::where('updated_at', '>=', $thirtyDaysAgo)->count();
        $studentVerificationRate = $totalUsers > 0 ? round(($verifiedStudents / $totalUsers) * 100, 1) : 0.0;

        // 2. Tournaments & Matches
        $totalTournaments = Tournament::count();
        $completedTournaments = Tournament::where('status', 'completed')->count();
        $tournamentCompletionRate = $totalTournaments > 0 ? round(($completedTournaments / $totalTournaments) * 100, 1) : 0.0;

        $totalMatches = TournamentMatch::count();
        $completedMatches = TournamentMatch::where('status', 'completed')->count();
        $matchCompletionRate = $totalMatches > 0 ? round(($completedMatches / $totalMatches) * 100, 1) : 0.0;

        $totalDisputes = Dispute::count();
        $disputeRate = $totalMatches > 0 ? round(($totalDisputes / $totalMatches) * 100, 2) : 0.0;

        // 3. Ticketing & Gate Attendance
        $totalTicketsSold = Ticket::count();
        $attendedTickets = Ticket::where('status', 'checked_in')->count();
        $attendanceRate = $totalTicketsSold > 0 ? round(($attendedTickets / $totalTicketsSold) * 100, 1) : 0.0;
        $totalTicketRevenue = (float) TicketOrder::where('status', 'paid')->sum('total_amount');

        // 4. Venues Performance
        $totalVenues = Venue::count();
        $verifiedVenues = Venue::where('verified', true)->count();

        // 5. University Participation Ranking
        $universityRankings = Profile::select('university', DB::raw('count(*) as student_count'))
            ->whereNotNull('university')
            ->where('university', '!=', '')
            ->groupBy('university')
            ->orderByDesc('student_count')
            ->limit(8)
            ->get();

        // 6. Learning & Awareness Completion
        $totalCourses = Course::where('is_published', true)->count();
        $totalEnrollments = CourseEnrollment::count();
        $completedEnrollments = CourseEnrollment::where('status', 'completed')->count();
        $learningCompletionRate = $totalEnrollments > 0 ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0.0;
        $totalCertificatesIssued = Certificate::count();

        // 7. Sponsorship Performance
        $totalSponsorships = Sponsorship::count();
        $activeSponsorships = Sponsorship::where('status', 'active')->count();
        $totalSponsorshipValue = (float) Sponsorship::whereIn('status', ['active', 'completed'])->sum('amount');

        return [
            'users' => [
                'total_users' => $totalUsers,
                'verified_students' => $verifiedStudents,
                'verification_rate' => $studentVerificationRate,
                'active_7d' => $activeUsers7d,
                'active_30d' => $activeUsers30d,
            ],
            'tournaments' => [
                'total' => $totalTournaments,
                'completed' => $completedTournaments,
                'completion_rate' => $tournamentCompletionRate,
                'total_matches' => $totalMatches,
                'completed_matches' => $completedMatches,
                'match_completion_rate' => $matchCompletionRate,
                'total_disputes' => $totalDisputes,
                'dispute_rate' => $disputeRate,
            ],
            'ticketing' => [
                'tickets_sold' => $totalTicketsSold,
                'tickets_attended' => $attendedTickets,
                'attendance_rate' => $attendanceRate,
                'gross_revenue' => $totalTicketRevenue,
                'currency' => 'ETB',
            ],
            'venues' => [
                'total_venues' => $totalVenues,
                'verified_venues' => $verifiedVenues,
            ],
            'universities' => $universityRankings,
            'learning' => [
                'published_courses' => $totalCourses,
                'total_enrollments' => $totalEnrollments,
                'completed_enrollments' => $completedEnrollments,
                'completion_rate' => $learningCompletionRate,
                'certificates_issued' => $totalCertificatesIssued,
            ],
            'sponsorship' => [
                'total_deals' => $totalSponsorships,
                'active_deals' => $activeSponsorships,
                'total_volume' => $totalSponsorshipValue,
                'currency' => 'ETB',
            ],
            'system_health' => [
                'status' => 'operational',
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'server_time' => now()->toIso8601String(),
            ],
        ];
    }

    public function generatePlatformMetricsCsv(): string
    {
        $metrics = $this->getExecutiveMetrics();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['Domain Category', 'Key Metric', 'Value']);

        // Users
        fputcsv($output, ['Identity', 'Total Registered Users', $metrics['users']['total_users']]);
        fputcsv($output, ['Identity', 'Verified Students', $metrics['users']['verified_students']]);
        fputcsv($output, ['Identity', 'Student Verification Rate (%)', $metrics['users']['verification_rate']]);
        fputcsv($output, ['Identity', '7-Day Active Users (WAU)', $metrics['users']['active_7d']]);
        fputcsv($output, ['Identity', '30-Day Active Users (MAU)', $metrics['users']['active_30d']]);

        // Tournaments
        fputcsv($output, ['Tournaments', 'Total Tournaments', $metrics['tournaments']['total']]);
        fputcsv($output, ['Tournaments', 'Tournament Completion Rate (%)', $metrics['tournaments']['completion_rate']]);
        fputcsv($output, ['Tournaments', 'Total Matches', $metrics['tournaments']['total_matches']]);
        fputcsv($output, ['Tournaments', 'Match Dispute Rate (%)', $metrics['tournaments']['dispute_rate']]);

        // Ticketing
        fputcsv($output, ['Ticketing', 'Total Tickets Sold', $metrics['ticketing']['tickets_sold']]);
        fputcsv($output, ['Ticketing', 'Checked-in Attendance (%)', $metrics['ticketing']['attendance_rate']]);
        fputcsv($output, ['Ticketing', 'Gross Ticketing Revenue (ETB)', $metrics['ticketing']['gross_revenue']]);

        // Learning
        fputcsv($output, ['Education', 'Course Enrollments', $metrics['learning']['total_enrollments']]);
        fputcsv($output, ['Education', 'Course Completion Rate (%)', $metrics['learning']['completion_rate']]);
        fputcsv($output, ['Education', 'Certificates Issued', $metrics['learning']['certificates_issued']]);

        // Sponsorship
        fputcsv($output, ['Sponsorship', 'Active Brand Deals', $metrics['sponsorship']['active_deals']]);
        fputcsv($output, ['Sponsorship', 'Total Sponsorship Volume (ETB)', $metrics['sponsorship']['total_volume']]);

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv ?: '';
    }
}
