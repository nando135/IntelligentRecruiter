<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_threads', function (Blueprint $table) {
            $table->id();
            $table->string('thread_id', 64)->unique();
            $table->string('title', 200)->default('New chat');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_threads');
    }
};