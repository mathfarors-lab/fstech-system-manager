<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->nullable()->constrained('systems')->nullOnDelete();
            $table->enum('type', ['income', 'expense'])->index();
            $table->string('category', 50)->index();
            $table->string('title', 255);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 10)->default('USD')->index();
            $table->date('transaction_date')->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['system_id', 'type']);
            $table->index(['currency', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
