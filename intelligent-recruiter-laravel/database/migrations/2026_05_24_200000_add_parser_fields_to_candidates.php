<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('parser_status')->nullable()->after('parsed_json');
            $table->text('parser_warning')->nullable()->after('parser_status');
            $table->string('source_filename')->nullable()->after('parser_warning');
            $table->string('file_hash')->nullable()->index()->after('source_filename');
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['parser_status', 'parser_warning', 'source_filename', 'file_hash']);
        });
    }
};
