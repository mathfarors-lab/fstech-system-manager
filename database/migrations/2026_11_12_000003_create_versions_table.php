<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_id')->constrained('systems')->cascadeOnDelete();
            $table->string('version', 50);
            $table->date('release_date')->nullable();
            $table->text('changelog')->nullable();
            $table->timestamps();

            $table->unique(['system_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('versions');
    }
};
