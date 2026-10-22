<?php

namespace App\Domain\Ticketing\Services;

use App\Domain\Identity\Services\AuditLogger;
use App\Domain\Ticketing\Models\Ticket;
use App\Domain\Ticketing\Models\TicketScan;
use App\Domain\Venues\Models\Event;
use App\Domain\Venues\Models\Venue;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TicketScanService
{
    public function __construct(
        protected QrTokenService $qrTokenService
    ) {}

    /**
     * Validate a gate scan in real time against the backend.
     */
    public function validateScan(
        User $scanner,
        string|array $qrPayload,
        ?int $venueId = null,
        ?int $eventId = null,
        ?string $deviceId = null,
        ?\DateTimeInterface $scannedAt = null
    ): array {
        $scannedAtTime = $scannedAt ? Carbon::parse($scannedAt) : now();

        // 1. Authorize Scanner
        $this->authorizeScanner($scanner, $venueId, $eventId);

        // 2. Parse Payload
        $payload = is_array($qrPayload) ? $qrPayload : json_decode($qrPayload, true);
        if (! is_array($payload) || empty($payload['ref']) || empty($payload['sig'])) {
            return [
                'success' => false,
                'status' => 'invalid',
                'reason' => 'Invalid QR code format.',
                'ticket' => null,
            ];
        }

        $ticketUuid = $payload['ref'];
        $signature = $payload['sig'];

        // PostgreSQL uuid columns reject non-UUID literals (SQLSTATE 22P02).
        // Treat malformed refs as invalid tickets instead of aborting the request.
        if (! is_string($ticketUuid) || ! Str::isUuid($ticketUuid)) {
            return [
                'success' => false,
                'status' => 'invalid',
                'reason' => 'Ticket reference not found in system.',
                'ticket' => null,
            ];
        }

        if (! is_string($signature) || $signature === '') {
            return [
                'success' => false,
                'status' => 'invalid',
                'reason' => 'Invalid QR code format.',
                'ticket' => null,
            ];
        }

        return DB::transaction(function () use ($scanner, $ticketUuid, $signature, $venueId, $eventId, $deviceId, $scannedAtTime) {
            $ticket = Ticket::where('uuid', $ticketUuid)
                ->with(['ticketType', 'event', 'tournament', 'user'])
                ->lockForUpdate()
                ->first();

            if (! $ticket) {
                return [
                    'success' => false,
                    'status' => 'invalid',
                    'reason' => 'Ticket reference not found in system.',
                    'ticket' => null,
                ];
            }

            // 3. Cryptographic Signature Verification
            if (! $this->qrTokenService->verifySignature($ticketUuid, $signature, $ticket)) {
                $this->logScan($ticket, $scanner, 'invalid', $venueId, $eventId, $deviceId, $scannedAtTime);

                return [
                    'success' => false,
                    'status' => 'invalid',
                    'reason' => 'Tampered QR code. Cryptographic signature verification failed.',
                    'ticket' => $ticket,
                ];
            }

            // Tickets must be event-scoped. Only super_admin may scan a ticket that
            // has no event_id (break-glass). authorizeScanner also allows super_admin
            // early when an event_id is present.
            if (! $ticket->event_id) {
                if (! $scanner->hasRole('super_admin')) {
                    throw new AuthorizationException('This ticket is not assigned to an event.');
                }
            } else {
                $this->authorizeScanner($scanner, null, (int) $ticket->event_id);
            }

            // 4. Check for Wrong Event
            if ($eventId && $ticket->event_id && (int) $ticket->event_id !== (int) $eventId) {
                $this->logScan($ticket, $scanner, 'wrong_event', $venueId, $eventId, $deviceId, $scannedAtTime);

                return [
                    'success' => false,
                    'status' => 'wrong_event',
                    'reason' => "Ticket is for another event (Event #{$ticket->event_id}).",
                    'ticket' => $ticket,
                ];
            }

            // 5. Check Ticket State
            if ($ticket->status === 'checked_in' || $ticket->status === 'used') {
                $this->logScan($ticket, $scanner, 'duplicate', $venueId, $eventId, $deviceId, $scannedAtTime);

                return [
                    'success' => false,
                    'status' => 'duplicate',
                    'reason' => 'Duplicate scan. Ticket was already checked in at '.$ticket->checked_in_at?->toDateTimeString().'.',
                    'ticket' => $ticket,
                ];
            }

            if ($ticket->status === 'expired' || ($ticket->expires_at && now()->gt($ticket->expires_at))) {
                $this->logScan($ticket, $scanner, 'expired', $venueId, $eventId, $deviceId, $scannedAtTime);

                return [
                    'success' => false,
                    'status' => 'expired',
                    'reason' => 'Ticket has expired.',
                    'ticket' => $ticket,
                ];
            }

            if ($ticket->status === 'revoked') {
                $this->logScan($ticket, $scanner, 'revoked', $venueId, $eventId, $deviceId, $scannedAtTime);

                return [
                    'success' => false,
                    'status' => 'revoked',
                    'reason' => 'Ticket has been revoked by the organizer.',
                    'ticket' => $ticket,
                ];
            }

            if ($ticket->status === 'refunded') {
                $this->logScan($ticket, $scanner, 'refunded', $venueId, $eventId, $deviceId, $scannedAtTime);

                return [
                    'success' => false,
                    'status' => 'refunded',
                    'reason' => 'Ticket was refunded and is no longer valid.',
                    'ticket' => $ticket,
                ];
            }

            if ($ticket->status === 'transferred') {
                $this->logScan($ticket, $scanner, 'invalid', $venueId, $eventId, $deviceId, $scannedAtTime);

                return [
                    'success' => false,
                    'status' => 'invalid',
                    'reason' => 'Ticket has been transferred to another attendee.',
                    'ticket' => $ticket,
                ];
            }

            if ($ticket->status !== 'active') {
                $this->logScan($ticket, $scanner, 'invalid', $venueId, $eventId, $deviceId, $scannedAtTime);

                return [
                    'success' => false,
                    'status' => 'invalid',
                    'reason' => "Ticket is not active (Status: {$ticket->status}).",
                    'ticket' => $ticket,
                ];
            }

            // 6. Successful Entry: Mark Checked In
            $ticket->update([
                'status' => 'checked_in',
                'checked_in_at' => $scannedAtTime,
                'checked_in_by_id' => $scanner->id,
                'venue_id' => $venueId ?? $ticket->venue_id,
            ]);

            $scan = $this->logScan($ticket, $scanner, 'valid', $venueId, $eventId, $deviceId, $scannedAtTime);

            AuditLogger::log(
                action: 'ticket.checked_in',
                user: $scanner,
                entityType: 'ticket',
                entityId: $ticket->id,
                metadata: [
                    'venue_id' => $venueId,
                    'event_id' => $eventId,
                    'device_id' => $deviceId,
                    'scan_id' => $scan->id,
                ]
            );

            return [
                'success' => true,
                'status' => 'valid',
                'reason' => 'Ticket successfully verified. Entry approved.',
                'ticket' => $ticket,
                'scan' => $scan,
            ];
        });
    }

    /**
     * Reconcile queued scans from an offline scanner device.
     */
    public function syncOfflineScans(User $scanner, array $queuedScans): array
    {
        $results = [];
        $syncedCount = 0;
        $duplicateCount = 0;
        $invalidCount = 0;

        foreach ($queuedScans as $item) {
            $qrPayload = $item['qr_payload'] ?? $item['payload'] ?? '';
            $venueId = $item['venue_id'] ?? null;
            $eventId = $item['event_id'] ?? null;
            $deviceId = $item['device_id'] ?? null;
            $scannedAt = ! empty($item['scanned_at']) ? Carbon::parse($item['scanned_at']) : now();

            $scanResult = $this->validateScan(
                $scanner,
                $qrPayload,
                $venueId,
                $eventId,
                $deviceId,
                $scannedAt
            );

            if ($scanResult['success']) {
                $syncedCount++;
            } elseif ($scanResult['status'] === 'duplicate') {
                $duplicateCount++;
            } else {
                $invalidCount++;
            }

            $results[] = [
                'ticket_ref' => is_array($qrPayload) ? ($qrPayload['ref'] ?? null) : null,
                'status' => $scanResult['status'],
                'reason' => $scanResult['reason'],
                'scanned_at' => $scannedAt->toISOString(),
            ];
        }

        return [
            'total_received' => count($queuedScans),
            'synced' => $syncedCount,
            'duplicates' => $duplicateCount,
            'invalid' => $invalidCount,
            'results' => $results,
        ];
    }

    /**
     * Authorize scanner permissions scoped by event and venue.
     */
    protected function authorizeScanner(User $scanner, ?int $venueId = null, ?int $eventId = null): void
    {
        if ($scanner->hasRole('super_admin')) {
            return;
        }

        if ($eventId === null && $venueId === null) {
            throw new AuthorizationException('A scanner must be assigned to the event or venue being scanned.');
        }

        if ($eventId) {
            $event = Event::query()->with('venue')->find($eventId);
            if ($event && ($event->organizer_id === $scanner->id || ($event->venue && $event->venue->isStaff($scanner)))) {
                return;
            }
        }

        if ($venueId) {
            $venue = Venue::find($venueId);
            if ($venue && $venue->isStaff($scanner)) {
                return;
            }
        }

        throw new AuthorizationException('You are not authorized to scan tickets for this venue or event.');
    }

    protected function logScan(
        Ticket $ticket,
        User $scanner,
        string $status,
        ?int $venueId = null,
        ?int $eventId = null,
        ?string $deviceId = null,
        ?\DateTimeInterface $scannedAt = null
    ): TicketScan {
        return TicketScan::create([
            'ticket_id' => $ticket->id,
            'scanner_user_id' => $scanner->id,
            'venue_id' => $venueId ?? $ticket->venue_id,
            'event_id' => $eventId ?? $ticket->event_id,
            'status' => $status,
            'scanned_at' => $scannedAt ?? now(),
            'device_id' => $deviceId,
        ]);
    }
}
