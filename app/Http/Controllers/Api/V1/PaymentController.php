<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PaymentSetting;
use App\Models\Transaction;
use App\Services\Payment\PaymentService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    public function publicConfig(): JsonResponse
    {
        return response()->json([
            'data' => $this->paymentService->getPublicConfig(),
        ]);
    }

    public function packages(Request $request): JsonResponse
    {
        $settings = PaymentSetting::getSettings();
        $single   = (float) $settings->default_single_session_price;

        $packages = Package::active()->get()->map(fn($p) => [
            'id'               => $p->id,
            'name'             => $p->name,
            'slug'             => $p->slug,
            'description'      => $p->description,
            'type'             => $p->type,
            'session_count'    => $p->session_count,
            'duration_days'    => $p->duration_days,
            'price'            => (float) $p->price,
            'discounted_price' => $p->discounted_price ? (float) $p->discounted_price : null,
            'effective_price'  => (float) $p->getEffectivePrice(),
            'is_popular'       => (bool) $p->is_popular,
            'features'         => $p->features ?? [],
        ]);

        return response()->json([
            'data' => [
                'single_session_price' => $single,
                'packages'             => $packages,
            ],
        ]);
    }

    public function checkoutPackage(Request $request, Package $package): JsonResponse
    {
        if (!$package->is_active) {
            return response()->json(['message' => 'Paket tidak aktif.'], 422);
        }

        try {
            $result = $this->paymentService->getSnapToken($request->user(), $package);

            return response()->json([
                'message' => 'Checkout created.',
                'data'    => [
                    'transaction_id' => $result['transaction']->id,
                    'order_id'       => $result['transaction']->order_id,
                    'amount'         => (float) $result['transaction']->gross_amount,
                    'snap_token'     => $result['snap_token'],
                    'redirect_url'   => $result['redirect_url'],
                    'expires_at'     => $result['transaction']->expires_at?->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat checkout. Silakan coba lagi.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function checkoutSingle(Request $request): JsonResponse
    {
        try {
            $result = $this->paymentService->createSingleSessionCharge($request->user());

            return response()->json([
                'message' => 'Checkout created.',
                'data'    => [
                    'transaction_id' => $result['transaction']->id,
                    'order_id'       => $result['transaction']->order_id,
                    'amount'         => (float) $result['transaction']->gross_amount,
                    'snap_token'     => $result['snap_token'],
                    'redirect_url'   => $result['redirect_url'],
                    'expires_at'     => $result['transaction']->expires_at?->toISOString(),
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat checkout. Silakan coba lagi.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function subscriptionStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'can_start_interview'     => $user->canStartInterview(),
                'has_active_subscription' => $user->hasActiveSubscription(),
                'subscribed_until'        => $user->subscribed_until?->toISOString(),
                'credit_sessions'         => (int) $user->credit_sessions,
                'sessions_done'           => $user->interviewSessions()->count(),
                'free_trial_remaining'    => max(0,
                    PaymentSetting::getSettings()->free_trial_sessions - $user->interviewSessions()->count()
                ),
            ],
        ]);
    }

    public function myTransactions(Request $request): JsonResponse
    {
        $perPage = (int) ($request->query('per_page', 10));
        $results = Transaction::where('user_id', $request->user()->id)
            ->with('package')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'data' => $results->map(fn($t) => [
                'id'                  => $t->id,
                'order_id'            => $t->order_id,
                'package_name'        => $t->package_name,
                'package_type'        => $t->package?->type,
                'gross_amount'        => (float) $t->gross_amount,
                'payment_type'        => $t->payment_type,
                'transaction_status'  => $t->transaction_status,
                'payment_link'        => $t->payment_link,
                'paid_at'             => $t->paid_at?->toISOString(),
                'expires_at'          => $t->expires_at?->toISOString(),
                'created_at'          => $t->created_at?->toISOString(),
            ]),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    public function transactionStatus(Request $request, Transaction $transaction): JsonResponse
    {
        abort_if($transaction->user_id !== $request->user()->id, 403);

        return response()->json([
            'data' => [
                'id'                  => $transaction->id,
                'order_id'            => $transaction->order_id,
                'package_name'        => $transaction->package_name,
                'gross_amount'        => (float) $transaction->gross_amount,
                'payment_type'        => $transaction->payment_type,
                'transaction_status'  => $transaction->transaction_status,
                'fraud_status'        => $transaction->fraud_status,
                'payment_link'        => $transaction->payment_link,
                'paid_at'             => $transaction->paid_at?->toISOString(),
                'expires_at'          => $transaction->expires_at?->toISOString(),
                'is_paid'             => $transaction->isPaid(),
                'is_final'            => $transaction->isFinal(),
                'created_at'          => $transaction->created_at?->toISOString(),
            ],
        ]);
    }

    public function midtransNotification(Request $request): JsonResponse
    {
        $payload = $request->all();

        try {
            $transaction = $this->paymentService->handleNotification($payload);

            return response()->json([
                'status' => 'ok',
                'transaction_status' => $transaction->transaction_status,
                'order_id' => $transaction->order_id,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'error'  => $e->getMessage(),
            ], 422);
        }
    }

    public function midtransFinish(Request $request): JsonResponse
    {
        $orderId = $request->query('order_id');
        $status  = $request->query('transaction_status');

        return response()->json([
            'message'            => 'Pembayaran selesai diproses.',
            'order_id'           => $orderId,
            'transaction_status' => $status,
        ]);
    }

    public function midtransUnfinish(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Pembayaran belum selesai. Silakan selesaikan pembayaran Anda.',
        ]);
    }

    public function midtransError(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Terjadi kesalahan pada pembayaran. Silakan coba lagi.',
        ], 422);
    }
}
