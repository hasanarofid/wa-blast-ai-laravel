<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_number');
            $table->enum('entity_type', ['customer', 'partner', 'unknown'])->default('unknown');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('session_id')->unique();
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            $table->json('context')->nullable(); // Store conversation context
            $table->string('current_step')->nullable(); // Current conversation step
            $table->datetime('last_activity');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_sessions');
    }
}; 