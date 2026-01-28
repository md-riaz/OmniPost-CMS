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
        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // facebook, linkedin, etc.
            $table->text('access_token'); // Will be encrypted
            $table->text('refresh_token')->nullable(); // Will be encrypted
            $table->timestamp('expires_at')->nullable();
            $table->json('scopes')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
            
            $table->index('platform');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
    }
};
