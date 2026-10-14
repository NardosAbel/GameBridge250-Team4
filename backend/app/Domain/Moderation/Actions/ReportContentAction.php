<?php

namespace App\Domain\Moderation\Actions;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Moderation\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class ReportContentAction
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    public function execute(User $reporter, Model $reportable, string $reason, ?string $details = null): Report
    {
        $existing = Report::where('reporter_id', $reporter->id)
            ->where('reportable_type', $reportable->getMorphClass())
            ->where('reportable_id', $reportable->getKey())
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            throw new InvalidArgumentException('You have already submitted a pending report for this item.');
        }

        $report = Report::create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $reportable->getMorphClass(),
            'reportable_id' => $reportable->getKey(),
            'reason' => $reason,
            'details' => $details,
            'status' => 'pending',
        ]);

        AuditLogger::log(
            action: 'report.created',
            user: $reporter,
            entityType: 'report',
            entityId: $report->id,
            metadata: ['reason' => $reason]
        );

        return $report;
    }
}
