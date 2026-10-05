<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->decimal('points_balance', 18, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('wallet_ledgers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('site_id')->nullable()->index();
            $t->string('type', 30);
            $t->decimal('gross_points', 18, 2);
            $t->decimal('fee_rate', 8, 4)->default(0);
            $t->decimal('fee_points', 18, 2)->default(0);
            $t->decimal('net_points', 18, 2);
            $t->string('order_id', 100)->nullable()->index();
            $t->string('idempotency_key', 100)->unique();
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('sites', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('domain')->unique();
            $t->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
            $t->string('client_id')->unique();
            $t->string('client_secret_hash');
            $t->string('status', 20)->default('pending');
            $t->json('scopes')->nullable();
            $t->timestamp('verified_at')->nullable();
            $t->timestamps();
        });
        Schema::create('seller_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $t->decimal('pending_income', 18, 2)->default(0);
            $t->decimal('available_income', 18, 2)->default(0);
            $t->decimal('total_income', 18, 2)->default(0);
            $t->timestamps();
            $t->unique(['user_id','site_id']);
        });
        Schema::create('seller_ledgers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $t->string('order_id',100)->unique();
            $t->decimal('gross_income',18,2);
            $t->decimal('platform_fee_rate',8,4);
            $t->decimal('platform_fee',18,2);
            $t->decimal('net_income',18,2);
            $t->string('status',20)->default('pending');
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
        Schema::create('marketplace_orders', function (Blueprint $t) {
            $t->id();
            $t->string('order_id',100)->unique();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $t->string('product_id',100);
            $t->decimal('points',18,2);
            $t->string('status',20)->default('paid');
            $t->string('idempotency_key',100)->unique();
            $t->json('metadata')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
        });
        Schema::create('withdrawals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $t->decimal('amount',18,2);
            $t->decimal('fee_rate',8,4);
            $t->decimal('fee_amount',18,2);
            $t->decimal('payout_amount',18,2);
            $t->string('status',30)->default('scheduled');
            $t->string('idempotency_key',100)->unique();
            $t->timestamp('scheduled_at')->nullable();
            $t->string('provider_tx_id')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('marketplace_orders');
        Schema::dropIfExists('seller_ledgers');
        Schema::dropIfExists('seller_accounts');
        Schema::dropIfExists('sites');
        Schema::dropIfExists('wallet_ledgers');
        Schema::dropIfExists('wallets');
    }
};
