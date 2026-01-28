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
        Schema::create('post_status_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->onDelete('cascade');
            $table->string('from_status', 50);
            $table->string('to_status', 50);
            $table->foreignId('changed_by')->constrained('users')->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->timestamp('changed_at');
            
            $table->index(['post_id', 'changed_at']);
            $table->index('changed_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_status_changes');
    }
};
