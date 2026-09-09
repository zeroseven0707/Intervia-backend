<?php

namespace App\Services\Payment;

use App\Models\Package;
use App\Models\PaymentSetting;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    private PaymentSetting $settings;
    private string $serverKey;
    private string $snapUrl;
    private string $apiUrl;

    public function __construct()
    {
        $this->settings  = PaymentSetting::getSettings();
        $this->serverKey = $this->settings->midtrans_server_key ?? config('midtrans.server_key', '');
        $this->snapUrl   = $this->settings->midtrans_production
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
        $this->apiUrl    = $this->settings->midtrans_production
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    public function getSnapToken(User $user, Package $package): array
    {
        $orderId = 'INV-' . $user->id . '-' . time() . '-' . rand(100, 999);
        $amount  = (int) round($package->getEffectivePrice());

        $itemDetails = [[
            'id'           => 'PKG-' . $package->id,
            'price'        => $amount,
            'quantity'     => 1,
            'name'         => $package->name,
        ]];

        $customerDetails = [
            'first_name' => explode(' ', $user->name)[0],
            'last_name'  => count(explode(' ', $user->name)) > 1 ? implode(' ', array_slice(explode(' ', $user->name), 1)) : '',
            'email'      => $user->email,
        ];

        $payload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $amount,
            ],
            'item_details'     => $itemDetails,
            'customer_details' => $customerDetails,
            'expiry'           => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'days',
                'duration'   => 1,
            ],
        ];

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->post("{$this->snapUrl}/transactions", $payload);

            if (!$response->successful()) {
                throw new Exception('Midtrans Snap request failed: ' . $response->body());
            }

            $data = $response->json();

            $transaction = Transaction::create([
                'user_id'        => $user->id,
                'package_id'     => $package->id,
                'order_id'       => $orderId,
                'package_name'   => $package->name,
                'gross_amount'   => $package->getEffectivePrice(),
                'payment_link'   => $data['redirect_url'] ?? null,
                'expires_at'     => now()->addDay(),
                'raw_response'   => $data,
            ]);

            return [
                'transaction'   => $transaction,
                'snap_token'    => $data['token'] ?? null,
                'redirect_url'  => $data['redirect_url'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Midtrans SnapToken error', [
                'user_id'  => $user->id,
                'package'  => $package->id,
                'error'    => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function createSingleSessionCharge(User $user): array
    {
        $settings = PaymentSetting::getSettings();
        $amount   = (int) round($settings->default_single_session_price);

        $orderId  = 'SINGLE-' . $user->id . '-' . time() . '-' . rand(100, 999);

        $itemDetails = [[
            'id'       => 'SINGLE-SESSION',
            'price'    => $amount,
            'quantity' => 1,
            'name'     => 'Single Session Interview',
        ]];

        $customerDetails = [
            'first_name' => explode(' ', $user->name)[0],
            'last_name'  => count(explode(' ', $user->name)) > 1 ? implode(' ', array_slice(explode(' ', $user->name), 1)) : '',
            'email'      => $user->email,
        ];

        $payload = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $amount,
            ],
            'item_details'     => $itemDetails,
            'customer_details' => $customerDetails,
            'expiry'           => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit'       => 'days',
                'duration'   => 1,
            ],
        ];

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->withHeaders(['Accept' => 'application/json', 'Content-Type' => 'application/json'])
                ->post("{$this->snapUrl}/transactions", $payload);

            if (!$response->successful()) {
                throw new Exception('Midtrans Snap request failed: ' . $response->body());
            }

            $data = $response->json();

            $transaction = Transaction::create([
                'user_id'        => $user->id,
                'package_id'     => null,
                'order_id'       => $orderId,
                'package_name'   => 'Single Session Interview',
                'gross_amount'   => $amount,
                'payment_link'   => $data['redirect_url'] ?? null,
                'expires_at'     => now()->addDay(),
                'raw_response'   => $data,
            ]);

            return [
                'transaction'   => $transaction,
                'snap_token'    => $data['token'] ?? null,
                'redirect_url'  => $data['redirect_url'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Midtrans SingleSession error', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function handleNotification(array $payload): Transaction
    {
        $orderId = $payload['order_id'] ?? null;
        if (!$orderId) {
            throw new Exception('Missing order_id in notification payload');
        }

        $transaction = Transaction::where('order_id', $orderId)->first();
        if (!$transaction) {
            throw new Exception("Transaction not found: {$orderId}");
        }

        $statusCode   = $payload['status_code'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        if (!$this->validateSignature($orderId, $payload['gross_amount'] ?? '', $statusCode, $signatureKey)) {
            throw new Exception('Invalid Midtrans signature key');
        }

        $transactionStatus = $payload['transaction_status'] ?? $transaction->transaction_status;
        $fraudStatus       = $payload['fraud_status'] ?? null;
        $paymentType       = $payload['payment_type'] ?? null;
        $midtransId        = $payload['transaction_id'] ?? null;

        $wasPaid = $transaction->isPaid();

        $transaction->update([
            'transaction_status'    => $transactionStatus,
            'fraud_status'          => $fraudStatus,
            'payment_type'          => $this->mapPaymentType($paymentType),
            'midtrans_transaction_id' => $midtransId,
            'raw_response'          => $payload,
            'paid_at'               => (in_array($transactionStatus, ['capture', 'settlement']) && !$wasPaid)
                ? now()
                : $transaction->paid_at,
        ]);

        if (!$wasPaid && $transaction->fresh()->isPaid()) {
            $this->fulfillOrder($transaction->fresh());
        }

        return $transaction->fresh();
    }

    private function validateSignature(string $orderId, string $grossAmount, string $statusCode, string $signature): bool
    {
        $raw = $orderId . $statusCode . $grossAmount . $this->serverKey;
        $calculated = hash('sha512', $raw);
        return hash_equals($calculated, (string) $signature);
    }

    private function mapPaymentType(?string $type): string
    {
        $map = [
            'credit_card'   => 'credit_card',
            'bank_transfer' => 'bank_transfer',
            'echannel'      => 'echannel',
            'gopay'         => 'gopay',
            'shopeepay'     => 'shopeepay',
        ];
        return $map[$type] ?? 'other';
    }

    private function fulfillOrder(Transaction $transaction): void
    {
        $user = $transaction->user;
        if (!$user) return;

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
    }

    public function getPublicConfig(): array
    {
        return [
            'client_key'   => $this->settings->midtrans_client_key ?? config('midtrans.client_key', ''),
            'is_production'=> (bool) ($this->settings->midtrans_production ?? config('midtrans.is_production', false)),
            'require_payment' => (bool) $this->settings->require_payment,
            'default_single_session_price' => (float) $this->settings->default_single_session_price,
            'free_trial_sessions' => (int) $this->settings->free_trial_sessions,
        ];
    }
}
