<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('systems', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('slug', 170)->unique();
            $table->text('description')->nullable();
            $table->string('stack', 50)->default('laravel')->index();
            $table->string('folder_path')->nullable();
            $table->string('repo_url')->nullable();
            $table->string('deploy_url')->nullable();
            $table->string('environment', 30)->default('development')->index();
            $table->string('status', 30)->default('active')->index();
            $table->string('icon', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('systems');
    }
};
