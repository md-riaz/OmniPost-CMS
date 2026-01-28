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
        Schema::table('post_variants', function (Blueprint $table) {
            if (!$this->indexExists('post_variants', ['status', 'scheduled_at'])) {
                $table->index(['status', 'scheduled_at']);
            }
        });

        // Skip metrics_snapshots - index already exists from Phase 6

        Schema::table('publication_attempts', function (Blueprint $table) {
            if (!$this->indexExists('publication_attempts', ['result', 'created_at'])) {
                $table->index(['result', 'created_at']);
            }
        });

        Schema::table('posts', function (Blueprint $table) {
            if (!$this->indexExists('posts', ['brand_id', 'status'])) {
                $table->index(['brand_id', 'status']);
            }
        });

        Schema::table('connected_social_accounts', function (Blueprint $table) {
            if (!$this->indexExists('connected_social_accounts', ['platform', 'status'])) {
                $table->index(['platform', 'status']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('post_variants', function (Blueprint $table) {
            $table->dropIndex(['status', 'scheduled_at']);
        });

        Schema::table('publication_attempts', function (Blueprint $table) {
            $table->dropIndex(['result', 'created_at']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['brand_id', 'status']);
        });

        Schema::table('connected_social_accounts', function (Blueprint $table) {
            $table->dropIndex(['platform', 'status']);
        });
    }

    private function indexExists(string $table, array $columns): bool
    {
        $indexes = \DB::select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name=?", [$table]);
        $indexName = $table . '_' . implode('_', $columns) . '_index';
        
        foreach ($indexes as $index) {
            if ($index->name === $indexName) {
                return true;
            }
        }
        
        return false;
    }
};
