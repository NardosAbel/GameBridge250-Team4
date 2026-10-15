<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Ticketing\Actions\CreateTicketOrderAction;
use App\Domain\Ticketing\Actions\CreateTicketTypeAction;
use App\Domain\Ticketing\Actions\TransferTicketAction;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketType;
use App\Domain\Ticketing\Services\TicketReportingService;
use App\Domain\Ticketing\Services\TicketScanService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ticketing\CheckoutTicketOrderRequest;
use App\Http\Requests\Ticketing\ScanTicketRequest;
use App\Http\Requests\Ticketing\StoreTicketTypeRequest;
use App\Http\Requests\Ticketing\SyncOfflineScansRequest;
use App\Http\Requests\Ticketing\TransferTicketRequest;
use App\Http\Resources\TicketOrderResource;
use App\Http\Resources\TicketResource;
use App\Http\Resources\TicketTypeResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketController extends Controller
{
    public function ticketTypes(Request $request): JsonResponse
    {
        $query = TicketType::query()->where('is_active', true);

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('tournament_id')) {
            $query->where('tournament_id', $request->input('tournament_id'));
        }

        $types = $query->get();

        return response()->json([
            'success' => true,
            'data' => TicketTypeResource::collection($types),
        ]);
    }

    public function storeTicketType(StoreTicketTypeRequest $request, CreateTicketTypeAction $action): JsonResponse
    {
        $ticketType = $action->execute($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Ticket tier created successfully.',
            'data' => new TicketTypeResource($ticketType),
        ], 201);
    }

    public function checkout(CheckoutTicketOrderRequest $request, CreateTicketOrderAction $action): JsonResponse
    {
        try {
            $order = $action->execute(
                $request->user(),
                $request->input('items'),
                $request->validated()
            );

            $order->loadMissing(['items.ticketType', 'tickets']);

            return response()->json([
                'success' => true,
                'message' => 'Ticket order placed successfully.',
                'data' => new TicketOrderResource($order),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function myTickets(Request $request): JsonResponse
    {
        $tickets = Ticket::where('user_id', $request->user()->id)
            ->with(['ticketType', 'event.venue', 'tournament'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => TicketResource::collection($tickets),
            'meta' => [
                'current_page' => $tickets->currentPage(),
                'last_page' => $tickets->lastPage(),
                'total' => $tickets->total(),
            ],
        ]);
    }

    public function showTicket(Ticket $ticket, Request $request): JsonResponse
    {
        if ($ticket->user_id !== $request->user()->id && ! $request->user()->hasRole('super-admin')) {
            abort(403, 'Unauthorized.');
        }

        $ticket->loadMissing(['ticketType', 'event.venue', 'tournament', 'user']);

        return response()->json([
            'success' => true,
            'data' => new TicketResource($ticket),
        ]);
    }

    public function transfer(TransferTicketRequest $request, Ticket $ticket, TransferTicketAction $action): JsonResponse
    {
        $recipient = null;
        if ($request->filled('recipient_email')) {
            $recipient = User::where('email', $request->input('recipient_email'))->firstOrFail();
        } elseif ($request->filled('recipient_id')) {
            $recipient = User::findOrFail($request->input('recipient_id'));
        }

        $newTicket = $action->execute($request->user(), $ticket, $recipient);

        return response()->json([
            'success' => true,
            'message' => "Ticket successfully transferred to {$recipient->name}.",
            'data' => new TicketResource($newTicket),
        ]);
    }

    public function scan(ScanTicketRequest $request, TicketScanService $scanService): JsonResponse
    {
        $result = $scanService->validateScan(
            scanner: $request->user(),
            qrPayload: $request->input('qr_payload'),
            venueId: $request->input('venue_id'),
            eventId: $request->input('event_id'),
            deviceId: $request->input('device_id'),
            scannedAt: $request->input('scanned_at')
        );

        $statusCode = $result['success'] ? 200 : ($result['status'] === 'duplicate' ? 409 : 422);

        return response()->json([
            'success' => $result['success'],
            'status' => $result['status'],
            'message' => $result['reason'],
            'ticket' => $result['ticket'] ? new TicketResource($result['ticket']) : null,
        ], $statusCode);
    }

    public function syncOfflineScans(SyncOfflineScansRequest $request, TicketScanService $scanService): JsonResponse
    {
        $report = $scanService->syncOfflineScans(
            scanner: $request->user(),
            queuedScans: $request->input('scans')
        );

        return response()->json([
            'success' => true,
            'message' => "Offline scans processed: {$report['synced']} valid, {$report['duplicates']} duplicates, {$report['invalid']} rejected.",
            'data' => $report,
        ]);
    }

    public function reports(Request $request, TicketReportingService $reportingService): JsonResponse
    {
        $report = $reportingService->generateReport(
            eventId: $request->input('event_id'),
            tournamentId: $request->input('tournament_id')
        );

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function exportCsv(Request $request, TicketReportingService $reportingService): StreamedResponse
    {
        $csv = $reportingService->exportCsv(
            eventId: $request->input('event_id'),
            tournamentId: $request->input('tournament_id')
        );

        $filename = 'attendance-report-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($csv): void {
            echo $csv;
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
