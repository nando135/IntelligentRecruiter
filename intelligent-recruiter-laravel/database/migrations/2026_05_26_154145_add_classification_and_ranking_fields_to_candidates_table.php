<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (! Schema::hasColumn('candidates', 'candidate_category')) {
                $table->string('candidate_category')->nullable()->index()->after('file_hash');
            }

            if (! Schema::hasColumn('candidates', 'classification_confidence')) {
                $table->decimal('classification_confidence', 5, 2)->nullable()->after('candidate_category');
            }

            if (! Schema::hasColumn('candidates', 'classification_reason')) {
                $table->text('classification_reason')->nullable()->after('classification_confidence');
            }

            if (! Schema::hasColumn('candidates', 'leaderboard_rank')) {
                $table->unsignedInteger('leaderboard_rank')->nullable()->index()->after('classification_reason');
            }

            if (! Schema::hasColumn('candidates', 'leaderboard_score')) {
                $table->decimal('leaderboard_score', 5, 2)->nullable()->after('leaderboard_rank');
            }

            if (! Schema::hasColumn('candidates', 'match_percentage')) {
                $table->decimal('match_percentage', 5, 2)->nullable()->after('leaderboard_score');
            }

            if (! Schema::hasColumn('candidates', 'ranking_reason')) {
                $table->text('ranking_reason')->nullable()->after('match_percentage');
            }

            if (! Schema::hasColumn('candidates', 'ranked_at')) {
                $table->timestamp('ranked_at')->nullable()->after('ranking_reason');
            }
        });
    }

    public function down(): void
    {
        $columns = [
            'candidate_category',
            'classification_confidence',
            'classification_reason',
            'leaderboard_rank',
            'leaderboard_score',
            'match_percentage',
            'ranking_reason',
            'ranked_at',
        ];

        foreach ($columns as $column) {
            if (Schema::hasColumn('candidates', $column)) {
                Schema::table('candidates', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
