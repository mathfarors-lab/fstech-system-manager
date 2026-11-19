<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            // Client information
            $table->string('client_name')->nullable()->after('owner_id');
            $table->string('client_phone', 50)->nullable()->after('client_name');
            $table->string('client_telegram', 100)->nullable()->after('client_phone');
            $table->string('client_email')->nullable()->after('client_telegram');
            $table->string('client_address')->nullable()->after('client_email');

            // Pricing & payment plan
            $table->decimal('price', 14, 2)->nullable()->after('client_address');
            $table->string('price_currency', 3)->default('USD')->after('price');
            $table->decimal('paid_amount', 14, 2)->default(0)->after('price_currency');
            $table->decimal('maintenance_amount', 14, 2)->nullable()->after('paid_amount');
            $table->string('maintenance_cycle', 20)->nullable()->after('maintenance_amount');
            $table->date('maintenance_starts_at')->nullable()->after('maintenance_cycle');
            $table->text('payment_notes')->nullable()->after('maintenance_starts_at');
        });
    }

    public function down(): void
    {
        Schema::table('systems', function (Blueprint $table) {
            $table->dropColumn([
                'client_name',
                'client_phone',
                'client_telegram',
                'client_email',
                'client_address',
                'price',
                'price_currency',
                'paid_amount',
                'maintenance_amount',
                'maintenance_cycle',
                'maintenance_starts_at',
                'payment_notes',
            ]);
        });
    }
};
