<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_session_id')->constrained()->onDelete('cascade');
            $table->string('message_id')->unique(); // WhatsApp message ID
            $table->enum('direction', ['inbound', 'outbound']);
            $table->enum('type', ['text', 'location', 'image', 'document', 'audio', 'video']);
            $table->text('content');
            $table->json('metadata')->nullable(); // Additional message data
            $table->enum('status', ['sent', 'delivered', 'read', 'failed'])->default('sent');
            $table->datetime('sent_at');
            $table->datetime('delivered_at')->nullable();
            $table->datetime('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_messages');
    }
}; 