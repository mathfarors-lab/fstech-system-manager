<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained()->cascadeOnDelete();
            $table->foreignId('finance_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['system_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_payments');
    }
};
