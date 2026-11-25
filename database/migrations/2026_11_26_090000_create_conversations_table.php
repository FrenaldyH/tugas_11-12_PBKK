<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    // One chat session in the Livewire workspace (an item in the sidebar). Always owned by one user.
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 120);
            $table->string('theme', 50)->default('default'); // system prompt theme (config/llm.php)
            $table->timestamps();

            // Sidebar lists a user's conversations, most recently active first
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
