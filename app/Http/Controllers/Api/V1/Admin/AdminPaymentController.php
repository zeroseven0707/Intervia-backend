<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use App\Models\PaymentSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminPaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
    ) {}

    public function getSettings(): JsonResponse
    {
        $settings = PaymentSetting::getSettings();

        return response()->json([
            'data' => [
                'midtrans' => [
                    'merchant_id'        => $settings->midtrans_merchant_id,
                    'client_key'         => $settings->midtrans_client_key ? '***' . substr($settings->midtrans_client_key, -4) : null,
                    'server_key_masked'  => $settings->midtrans_server_key ? '***' . substr($settings->midtrans_server_key, -4) : null,
                    'has_server_key'     => !empty($settings->midtrans_server_key),
                    'has_client_key'     => !empty($settings->midtrans_client_key),
                    'is_production'      => (bool) $settings->midtrans_production,
                ],
                'default_single_session_price' => (float) $settings->default_single_session_price,
                'require_payment'     => (bool) $settings->require_payment,
                'free_trial_sessions' => (int) $settings->free_trial_sessions,
                'public_config'       => $this->paymentService->getPublicConfig(),
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'midtrans_merchant_id'             => 'sometimes|nullable|string|max:50',
            'midtrans_server_key'              => 'sometimes|nullable|string|max:255',
            'midtrans_client_key'              => 'sometimes|nullable|string|max:255',
            'midtrans_production'              => 'sometimes|boolean',
            'default_single_session_price'     => 'sometimes|numeric|min:1000|max:999999999',
            'require_payment'                  => 'sometimes|boolean',
            'free_trial_sessions'              => 'sometimes|integer|min:0|max:1000',
        ]);

        $settings = PaymentSetting::getSettings();

        $envUpdates = [];
        if (isset($data['midtrans_server_key']))  $envUpdates['MIDTRANS_SERVER_KEY']   = $data['midtrans_server_key'];
        if (isset($data['midtrans_client_key']))  $envUpdates['MIDTRANS_CLIENT_KEY']   = $data['midtrans_client_key'];
        if (isset($data['midtrans_merchant_id'])) $envUpdates['MIDTRANS_MERCHANT_ID']  = $data['midtrans_merchant_id'];
        if (isset($data['midtrans_production']))  $envUpdates['MIDTRANS_PRODUCTION']   = $data['midtrans_production'] ? 'true' : 'false';

        if (!empty($envUpdates)) {
            $envPath    = base_path('.env');
            $envContent = @file_get_contents($envPath);

            if ($envContent !== false) {
                foreach ($envUpdates as $envKey => $envValue) {
                    $envValue = is_string($envValue) && str_contains($envValue, ' ')
                        ? "\"{$envValue}\""
                        : (string) $envValue;

                    if (preg_match("/^{$envKey}=/m", $envContent)) {
                        $envContent = preg_replace(
                            "/^{$envKey}=.*/m",
                            "{$envKey}={$envValue}",
                            $envContent
                        );
                    } else {
                        $envContent .= "\n{$envKey}={$envValue}";
                    }
                }
                @file_put_contents($envPath, $envContent);
                Artisan::call('config:clear');
            }
        }

        $settings->update($data);

        return response()->json([
            'message' => 'Pengaturan pembayaran disimpan.',
            'data'    => $settings->fresh(),
        ]);
    }

    public function summary(): JsonResponse
    {
        $totalRevenue = Transaction::whereIn('transaction_status', ['capture', 'settlement'])
            ->sum('gross_amount');

        $paidCount     = Transaction::whereIn('transaction_status', ['capture', 'settlement'])->count();
        $pendingCount  = Transaction::where('transaction_status', 'pending')->count();
        $failedCount   = Transaction::whereIn('transaction_status', ['deny', 'cancel', 'expire'])->count();

        $totalUsers       = User::count();
        $activeSubsUsers  = User::where('subscribed_until', '>', now())->count();
        $usersWithCredit  = User::where('credit_sessions', '>', 0)->count();
        $paidUsers        = User::whereHas('transactions', fn($q) => $q->whereIn('transaction_status', ['capture', 'settlement']))->count();

        $totalSessions  = InterviewSession::count();

        $recentTransactions = Transaction::with('user')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'order_id'   => $t->order_id,
                'user'       => $t->user ? ['id' => $t->user->id, 'name' => $t->user->name, 'email' => $t->user->email] : null,
                'package'    => $t->package_name,
                'amount'     => $t->gross_amount,
                'status'     => $t->transaction_status,
                'created_at' => $t->created_at?->toISOString(),
                'paid_at'    => $t->paid_at?->toISOString(),
            ]);

        return response()->json([
            'data' => [
                'revenue' => [
                    'total'       => (float) $totalRevenue,
                    'paid_count'  => $paidCount,
                    'pending'     => $pendingCount,
                    'failed'      => $failedCount,
                ],
                'users' => [
                    'total'          => $totalUsers,
                    'paid_users'     => $paidUsers,
                    'active_subs'    => $activeSubsUsers,
                    'with_credit'    => $usersWithCredit,
                ],
                'sessions' => [
                    'total' => $totalSessions,
                ],
                'recent_transactions' => $recentTransactions,
            ],
        ]);
    }

    public function transactions(Request $request): JsonResponse
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Transaction::with(['user', 'package'])->latest();

        if ($status) {
            $query->where('transaction_status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")
                                                        ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $perPage = (int) ($request->query('per_page', 20));
        $results = $query->paginate($perPage);

        return response()->json([
            'data' => $results->map(fn($t) => [
                'id'                  => $t->id,
                'order_id'            => $t->order_id,
                'midtrans_id'         => $t->midtrans_transaction_id,
                'user'                => $t->user ? [
                    'id'    => $t->user->id,
                    'name'  => $t->user->name,
                    'email' => $t->user->email,
                ] : null,
                'package_id'          => $t->package_id,
                'package_name'        => $t->package_name,
                'gross_amount'        => (float) $t->gross_amount,
                'payment_type'        => $t->payment_type,
                'transaction_status'  => $t->transaction_status,
                'fraud_status'        => $t->fraud_status,
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

    public function showTransaction(Transaction $transaction): JsonResponse
    {
        return response()->json([
            'data' => [
                'id'                  => $transaction->id,
                'order_id'            => $transaction->order_id,
                'midtrans_id'         => $transaction->midtrans_transaction_id,
                'user'                => $transaction->user ? [
                    'id'    => $transaction->user->id,
                    'name'  => $transaction->user->name,
                    'email' => $transaction->user->email,
                ] : null,
                'package'             => $transaction->package,
                'package_name'        => $transaction->package_name,
                'gross_amount'        => (float) $transaction->gross_amount,
                'payment_type'        => $transaction->payment_type,
                'transaction_status'  => $transaction->transaction_status,
                'fraud_status'        => $transaction->fraud_status,
                'payment_link'        => $transaction->payment_link,
                'paid_at'             => $transaction->paid_at?->toISOString(),
                'expires_at'          => $transaction->expires_at?->toISOString(),
                'raw_response'        => $transaction->raw_response,
                'created_at'          => $transaction->created_at?->toISOString(),
                'updated_at'          => $transaction->updated_at?->toISOString(),
            ],
        ]);
    }

    public function updateTransactionStatus(Request $request, Transaction $transaction): JsonResponse
    {
        $data = $request->validate([
            'transaction_status' => 'required|in:pending,capture,settlement,deny,cancel,expire,refund,chargeback',
        ]);

        $wasPaid = $transaction->isPaid();
        $transaction->update($data);

        if (!$wasPaid && $transaction->fresh()->isPaid()) {
            $user = $transaction->user;
            if ($user) {
                $package = $transaction->package;
                if ($package) {
                    if ($package->type === 'session' && $package->session_count > 0) {
                        $user->addSessions($package->session_count);
                    } elseif ($package->type === 'subscription' && $package->duration_days > 0) {
                        $user->extendSubscription($package->duration_days);
                    }
                } else {
                    $user->addSessions(1);
                }
                if (!$transaction->paid_at) {
                    $transaction->update(['paid_at' => now()]);
                }
            }
        }

        return response()->json([
            'message' => 'Status transaksi diperbarui.',
            'data'    => $transaction->fresh(),
        ]);
    }

    public function adjustUserCredit(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'type'       => 'required|in:add_sessions,remove_sessions,extend_subscription,reset',
            'sessions'   => 'required_if:type,add_sessions,remove_sessions|integer|min:1',
            'days'       => 'required_if:type,extend_subscription|integer|min:1',
        ]);

        switch ($data['type']) {
            case 'add_sessions':
                $user->addSessions($data['sessions']);
                break;
            case 'remove_sessions':
                $user->decrement('credit_sessions', min($data['sessions'], $user->credit_sessions));
                break;
            case 'extend_subscription':
                $user->extendSubscription($data['days']);
                break;
            case 'reset':
                $user->update([
                    'credit_sessions'  => 0,
                    'subscribed_until' => null,
                ]);
                break;
        }

        return response()->json([
            'message' => 'Kredit user diperbarui.',
            'data'    => [
                'credit_sessions'  => $user->fresh()->credit_sessions,
                'subscribed_until' => $user->fresh()->subscribed_until?->toISOString(),
            ],
        ]);
    }
}
