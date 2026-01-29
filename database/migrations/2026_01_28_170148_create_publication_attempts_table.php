<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('publication_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_variant_id')->constrained()->cascadeOnDelete();
            $table->integer('attempt_no')->default(1);
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('result')->nullable(); // success, fail
            $table->string('external_post_id')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamps();
            
            $table->index(['post_variant_id', 'attempt_no']);
            $table->index('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publication_attempts');
    }
};
