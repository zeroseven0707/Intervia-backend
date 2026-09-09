<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_settings', function (Blueprint $table) {
            $table->id();
            $table->string('midtrans_server_key')->nullable();
            $table->string('midtrans_client_key')->nullable();
            $table->boolean('midtrans_production')->default(false);
            $table->string('midtrans_merchant_id')->nullable();

            $table->decimal('default_single_session_price', 15, 2)->default(3000);

            $table->boolean('require_payment')->default(true);
            $table->integer('free_trial_sessions')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_settings');
    }
};
