<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained('systems')->cascadeOnDelete();
            $table->foreignId('version_id')->nullable()->constrained('versions')->nullOnDelete();
            $table->string('version_label', 50);
            $table->string('environment', 30)->default('development')->index();
            $table->string('status', 30)->default('planned')->index();
            $table->foreignId('built_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('built_at')->nullable();
            $table->timestamp('deployed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['system_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builds');
    }
};
