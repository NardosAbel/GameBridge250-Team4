<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Identity\Enums\RoleEnum;
use App\Domain\Payments\Models\Payment;
use App\Domain\Payments\Models\Payout;
use App\Domain\Payments\Models\Refund;
use App\Domain\Payments\Services\PaymentProcessingService;
use App\Domain\Payments\Services\PayoutService;
use App\Domain\Payments\Services\RefundService;
use App\Domain\Ticketing\Models\TicketOrder;
use App\Domain\Venues\Models\Event;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\InitiatePaymentRequest;
use App\Http\Requests\Payments\ProcessRefundRequest;
use App\Http\Requests\Payments\RequestPayoutRequest;
use App\Http\Requests\Payments\RequestRefundRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\PayoutResource;
use App\Http\Resources\RefundResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiate(InitiatePaymentRequest $request, PaymentProcessingService $processingService): JsonResponse
    {
        $order = TicketOrder::findOrFail($request->input('order_id'));

        $payment = $processingService->initiatePayment(
            $order,
            $request->input('provider'),
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment initiated.',
            'data' => new PaymentResource($payment),
        ]);
    }

    public function webhook(Request $request, string $provider, PaymentProcessingService $processingService): JsonResponse
    {
        $signature = $request->header('x-chapa-signature')
            ?? $request->header('x-telebirr-signature')
            ?? $request->header('x-signature')
            ?? $request->input('signature');

        $webhook = $processingService->processWebhook(
            provider: $provider,
            payload: $request->all(),
            signature: $signature,
            headers: $request->headers->all()
        );

        $isDuplicate = ($webhook->status === 'duplicate');
        $isFailed = ($webhook->status === 'failed');

        return response()->json([
            'success' => ! $isFailed,
            'status' => $webhook->status,
            'message' => $isDuplicate ? 'Duplicate webhook acknowledged.' : ($isFailed ? 'Webhook verification failed.' : 'Webhook processed successfully.'),
        ], $isFailed ? 400 : 200);
    }

    public function reconcile(Request $request, Payment $payment, PaymentProcessingService $processingService): JsonResponse
    {
        $user = $request->user();
        $order = $payment->order;
        $event = $order?->event_id ? Event::query()->find($order->event_id) : null;
        $allowed = $user->hasRole(RoleEnum::SUPER_ADMIN->value)
            || $payment->user_id === $user->id
            || ($event && $event->organizer_id === $user->id);

        if (! $allowed) {
            abort(403, 'You cannot reconcile this payment.');
        }

        $reconciled = $processingService->reconcilePayment($payment);

        return response()->json([
            'success' => true,
            'data' => new PaymentResource($reconciled),
        ]);
    }

    public function requestRefund(RequestRefundRequest $request, TicketOrder $order, RefundService $refundService): JsonResponse
    {
        $refund = $refundService->requestRefund(
            order: $order,
            requester: $request->user(),
            amount: (float) $request->input('amount'),
            reason: $request->input('reason'),
            reasonCategory: $request->input('reason_category', 'attendee_request')
        );

        return response()->json([
            'success' => true,
            'message' => 'Refund request submitted for organizer review.',
            'data' => new RefundResource($refund),
        ], 201);
    }

    public function processRefund(ProcessRefundRequest $request, Refund $refund, RefundService $refundService): JsonResponse
    {
        $processed = $refundService->processRefund(
            refund: $refund,
            processor: $request->user(),
            approve: (bool) $request->input('approve'),
            rejectionReason: $request->input('rejection_reason')
        );

        return response()->json([
            'success' => true,
            'message' => $processed->status === 'approved' ? 'Refund approved and tickets revoked.' : 'Refund rejected.',
            'data' => new RefundResource($processed),
        ]);
    }

    public function requestPayout(RequestPayoutRequest $request, PayoutService $payoutService): JsonResponse
    {
        $payout = $payoutService->requestPayout(
            organizer: $request->user(),
            grossAmount: (float) $request->input('gross_amount'),
            accountNumber: $request->input('account_number'),
            provider: $request->input('provider', 'telebirr'),
            eventId: $request->input('event_id'),
            tournamentId: $request->input('tournament_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Payout request submitted with transparent platform fee deduction.',
            'data' => new PayoutResource($payout),
        ], 201);
    }

    public function processPayout(Request $request, Payout $payout, PayoutService $payoutService): JsonResponse
    {
        if (! $request->user()->hasRole(RoleEnum::SUPER_ADMIN->value)) {
            abort(403, 'Unauthorized.');
        }

        $approve = (bool) $request->input('approve', true);
        $processed = $payoutService->processPayout($payout, $request->user(), $approve);

        return response()->json([
            'success' => true,
            'message' => "Payout {$processed->status}.",
            'data' => new PayoutResource($processed),
        ]);
    }
}
