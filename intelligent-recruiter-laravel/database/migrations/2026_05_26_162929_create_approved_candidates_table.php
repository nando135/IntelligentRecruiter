<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approved_candidates', function (Blueprint $table) {
            $table->id();

            $table->foreignId('candidate_id')->unique()->constrained('candidates')->cascadeOnDelete();

            $table->string('full_name_snapshot')->nullable();
            $table->string('email_snapshot')->nullable();
            $table->string('candidate_category_snapshot')->nullable()->index();
            $table->unsignedInteger('leaderboard_rank_snapshot')->nullable();
            $table->decimal('leaderboard_score_snapshot', 5, 2)->nullable();
            $table->decimal('match_percentage_snapshot', 5, 2)->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->index();
            $table->text('approval_note')->nullable();
            $table->string('approval_source')->nullable();

            $table->foreignId('email_template_id')->nullable()->constrained('email_templates')->nullOnDelete();
            $table->string('email_status')->default('not_sent')->index();
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_error')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approved_candidates');
    }
};
