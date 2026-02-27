<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('department')->nullable();
            $table->string('category')->nullable();
            $table->string('name');
            $table->string('objective')->nullable();
            $table->decimal('budget', 12, 2)->nullable();
            $table->integer('kpi_target')->nullable();
            $table->timestamp('start_at')->nullable();
            $table->timestamp('end_at')->nullable();
            $table->string('status')->default('planned');
            $table->timestamps();

            $table->index(['brand_id', 'status']);
            $table->index(['department', 'category']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('brand_id')->constrained()->nullOnDelete();
            $table->timestamp('approval_due_at')->nullable()->after('approved_at');
            $table->timestamp('approval_escalated_at')->nullable()->after('approval_due_at');

            $table->index(['status', 'approval_due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'approval_due_at']);
            $table->dropColumn(['approval_escalated_at', 'approval_due_at']);
            $table->dropConstrainedForeignId('campaign_id');
        });

        Schema::dropIfExists('campaigns');
    }
};
