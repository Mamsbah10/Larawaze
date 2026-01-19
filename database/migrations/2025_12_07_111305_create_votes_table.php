<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['up','down'])->default('up');
            $table->timestamps();
            $table->unique(['event_id','user_id']); // un vote par user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
