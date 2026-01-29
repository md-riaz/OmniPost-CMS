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
        Schema::create('metrics_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_variant_id')->constrained()->cascadeOnDelete();
            $table->timestamp('captured_at');
            $table->integer('likes')->default(0);
            $table->integer('comments')->default(0);
            $table->integer('shares')->default(0);
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->json('raw_metrics')->nullable();
            $table->timestamps();
            
            $table->index(['post_variant_id', 'captured_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('metrics_snapshots');
    }
};
