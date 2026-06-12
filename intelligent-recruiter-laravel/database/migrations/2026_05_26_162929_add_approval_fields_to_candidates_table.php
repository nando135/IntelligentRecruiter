<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (! Schema::hasColumn('candidates', 'approval_status')) {
                $table->string('approval_status')->default('pending')->index()->after('ranked_at');
            }

            if (! Schema::hasColumn('candidates', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approval_status');
            }

            if (! Schema::hasColumn('candidates', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('candidates', 'approval_note')) {
                $table->text('approval_note')->nullable()->after('approved_by');
            }

            if (! Schema::hasColumn('candidates', 'approval_source')) {
                $table->string('approval_source')->nullable()->after('approval_note');
            }

            if (! Schema::hasColumn('candidates', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('approval_source');
            }

            if (! Schema::hasColumn('candidates', 'email_status')) {
                $table->string('email_status')->default('not_sent')->index()->after('email_sent_at');
            }

            if (! Schema::hasColumn('candidates', 'email_error')) {
                $table->text('email_error')->nullable()->after('email_status');
            }

            if (! Schema::hasColumn('candidates', 'email_template_id')) {
                $table->unsignedBigInteger('email_template_id')->nullable()->after('email_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'approved_by')) {
                $table->dropConstrainedForeignId('approved_by');
            }

            $columns = [
                'approval_status',
                'approved_at',
                'approval_note',
                'approval_source',
                'email_sent_at',
                'email_status',
                'email_error',
                'email_template_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('candidates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
