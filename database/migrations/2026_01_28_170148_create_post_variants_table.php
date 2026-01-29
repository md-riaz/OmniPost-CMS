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
        Schema::create('post_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // facebook, linkedin
            $table->foreignId('connected_social_account_id')->constrained()->cascadeOnDelete();
            $table->text('text_override')->nullable();
            $table->json('media_override')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->string('status')->default('draft'); // draft, scheduled, publishing, published, failed
            $table->timestamps();
            
            $table->index(['post_id', 'platform']);
            $table->index(['connected_social_account_id', 'status']);
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_variants');
    }
};
