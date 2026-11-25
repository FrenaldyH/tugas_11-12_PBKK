<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    // Background file scan (Challenge 3): the queue job writes progress here, the Livewire panel polls it.
    public function up(): void
    {
        Schema::create('scan_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status', 10)->default('queued'); // queued | running | done | failed
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->string('current_file')->nullable();
            $table->json('hashes')->nullable();   // {relative path: sha256}
            $table->json('findings')->nullable(); // [{file, line, rule}] possible hard-coded secrets
            $table->json('changed')->nullable();  // files added/changed/removed since the previous finished scan
            $table->string('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scan_runs');
    }
};
