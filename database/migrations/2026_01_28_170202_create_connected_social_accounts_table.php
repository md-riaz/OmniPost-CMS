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
        Schema::create('connected_social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('platform'); // facebook, linkedin
            $table->string('external_account_id'); // page/org id from platform
            $table->string('display_name');
            $table->foreignId('token_id')->nullable()->constrained('oauth_tokens')->nullOnDelete();
            $table->string('status')->default('connected'); // connected, expired, revoked
            $table->timestamps();
            
            $table->index(['brand_id', 'platform']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connected_social_accounts');
    }
};
