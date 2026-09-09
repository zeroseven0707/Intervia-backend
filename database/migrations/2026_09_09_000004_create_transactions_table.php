<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();

            $table->string('order_id')->unique();
            $table->string('midtrans_transaction_id')->nullable();

            $table->string('package_name')->nullable();
            $table->decimal('gross_amount', 15, 2);

            $table->enum('payment_type', ['credit_card', 'bank_transfer', 'echannel', 'gopay', 'shopeepay', 'other'])->nullable();
            $table->enum('transaction_status', [
                'pending', 'capture', 'settlement', 'deny', 'cancel', 'expire', 'refund', 'chargeback'
            ])->default('pending');

            $table->string('fraud_status')->nullable();
            $table->text('payment_link')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->json('raw_response')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'transaction_status']);
            $table->index(['order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
