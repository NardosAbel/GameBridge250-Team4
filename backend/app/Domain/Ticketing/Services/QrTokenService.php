<?php

namespace App\Domain\Ticketing\Services;

use App\Domain\Ticketing\Models\Ticket;
use InvalidArgumentException;

class QrTokenService
{
    /**
     * Generate an offline-verifiable, signed QR payload for a ticket.
     * Contains ONLY the unique reference and signature.
     * Never contains personal, student ID, or payment data.
     *
     * @return array{ref: string, sig: string}
     */
    public function generatePayload(Ticket $ticket): array
    {
        $secretKey = config('app.key');
        if (empty($secretKey)) {
            throw new InvalidArgumentException('Application key is not set.');
        }

        $signature = hash_hmac('sha256', "ticket:{$ticket->uuid}:{$ticket->id}", $secretKey);
        $tokenHash = hash('sha256', $ticket->uuid.$signature);

        $ticket->update([
            'qr_signature' => $signature,
            'qr_token_hash' => $tokenHash,
        ]);

        return [
            'ref' => $ticket->uuid,
            'sig' => $signature,
        ];
    }

    /**
     * Verify the cryptographic signature of a ticket reference.
     */
    public function verifySignature(string $ticketUuid, string $signature, Ticket $ticket): bool
    {
        $secretKey = config('app.key');
        $expectedSignature = hash_hmac('sha256', "ticket:{$ticketUuid}:{$ticket->id}", $secretKey);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate a standalone token package for unit testing or verification.
     *
     * @return array{payload: string, signature: string, ref: string}
     */
    public function generateToken(string $ref): array
    {
        $secretKey = config('app.key');
        $signature = hash_hmac('sha256', "ticket:{$ref}", $secretKey);
        $payload = json_encode(['ref' => $ref, 'sig' => $signature]);

        return [
            'payload' => $payload,
            'signature' => $signature,
            'ref' => $ref,
        ];
    }

    /**
     * Verify a standalone token payload against a signature.
     */
    public function verifyToken(string $payload, string $signature): bool
    {
        $data = is_array($payload) ? $payload : json_decode($payload, true);
        if (! is_array($data) || empty($data['ref']) || empty($data['sig'])) {
            return false;
        }

        $secretKey = config('app.key');
        $expected = hash_hmac('sha256', "ticket:{$data['ref']}", $secretKey);

        return hash_equals($expected, $signature) && hash_equals($signature, $data['sig']);
    }

    /**
     * Extract ticket reference from a QR payload.
     */
    public function extractReference(string $payload): ?string
    {
        $data = is_array($payload) ? $payload : json_decode($payload, true);

        return is_array($data) ? ($data['ref'] ?? null) : null;
    }
}
