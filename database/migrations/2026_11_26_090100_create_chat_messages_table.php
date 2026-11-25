<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    // Every bubble of a conversation, in order: the user's prompt and the AI's answer are separate rows.
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id(); // auto-increment id = order of the messages
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            // Owner on every row too, so queries can always be scoped to auth()->id()
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 10); // user | assistant
            $table->longText('content');
            $table->string('status', 10)->default('success'); // success | failed (assistant only)
            $table->string('provider', 30)->nullable(); // ollama | senopati, null for user messages
            $table->string('model', 100)->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->timestamps();

            $table->index(['conversation_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
