<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Moderation\Actions\ReportContentAction;
use App\Domain\Moderation\Actions\TakeModerationAction;
use App\Domain\Moderation\Models\ModerationCase;
use App\Domain\Moderation\Models\Report;
use App\Domain\Social\Models\Comment;
use App\Domain\Social\Models\Post;
use App\Http\Controllers\Controller;
use App\Http\Requests\Moderation\StoreReportRequest;
use App\Http\Requests\Moderation\TakeModerationActionRequest;
use App\Http\Resources\ReportResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class ModerationController extends Controller
{
    public function report(StoreReportRequest $request, ReportContentAction $action): JsonResponse
    {
        $type = $request->validated('reportable_type');
        $id = $request->validated('reportable_id');

        $reportable = match ($type) {
            'post' => Post::findOrFail($id),
            'comment' => Comment::findOrFail($id),
            'user' => User::findOrFail($id),
            default => throw new InvalidArgumentException('Unsupported reportable type.'),
        };

        $report = $action->execute(
            reporter: $request->user(),
            reportable: $reportable,
            reason: $request->validated('reason'),
            details: $request->validated('details')
        );

        return response()->json([
            'message' => 'Report submitted successfully. Our moderation team will review it.',
            'data' => new ReportResource($report),
        ], 201);
    }

    public function queue(Request $request): JsonResponse
    {
        Gate::authorize('viewQueue', ModerationCase::class);

        $reports = Report::query()
            ->with(['reporter.profile', 'reviewer'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => ReportResource::collection($reports),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
            ],
        ]);
    }

    public function action(TakeModerationActionRequest $request, TakeModerationAction $action): JsonResponse
    {
        Gate::authorize('takeAction', ModerationCase::class);

        $targetUser = User::findOrFail($request->validated('target_user_id'));
        $report = $request->filled('report_id') ? Report::find($request->validated('report_id')) : null;

        $contentToDelete = null;
        if ($request->validated('action') === 'delete_content' && $request->filled('content_id')) {
            $contentToDelete = match ($request->validated('content_type')) {
                'post' => Post::find($request->validated('content_id')),
                'comment' => Comment::find($request->validated('content_id')),
                default => null,
            };
        }

        $expiresAt = $request->filled('expires_at') ? Carbon::parse($request->validated('expires_at')) : null;

        $case = $action->execute(
            moderator: $request->user(),
            targetUser: $targetUser,
            action: $request->validated('action'),
            reason: $request->validated('reason'),
            report: $report,
            contentToDelete: $contentToDelete,
            expiresAt: $expiresAt
        );

        return response()->json([
            'message' => "Moderation action '{$request->validated('action')}' executed successfully.",
            'data' => $case,
        ]);
    }
}
