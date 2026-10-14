<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Models\AuditLog;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\AuditLogResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends ApiController
{
    /**
     * List audit logs with pagination and filters (authorized admins/moderators only).
     */
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->latest('id');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $logs = $query->paginate($request->integer('per_page', 20));

        return $this->successResponse(
            AuditLogResource::collection($logs),
            'Audit logs retrieved successfully.',
            200,
            [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ]
        );
    }
}
