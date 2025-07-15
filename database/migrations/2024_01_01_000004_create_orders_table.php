<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('partner_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            $table->enum('status', [
                'pending', 'confirmed', 'assigned', 'picked_up', 
                'in_progress', 'completed', 'cancelled', 'failed'
            ])->default('pending');
            
            // Pickup location
            $table->text('pickup_address');
            $table->decimal('pickup_latitude', 10, 8);
            $table->decimal('pickup_longitude', 11, 8);
            
            // Destination location
            $table->text('destination_address');
            $table->decimal('destination_latitude', 10, 8);
            $table->decimal('destination_longitude', 11, 8);
            
            // Distance and pricing
            $table->decimal('distance_km', 8, 2);
            $table->decimal('base_price', 10, 2);
            $table->decimal('distance_price', 10, 2);
            $table->decimal('total_price', 10, 2);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('partner_earnings', 10, 2)->default(0);
            
            // Additional details
            $table->text('notes')->nullable();
            $table->json('items')->nullable(); // For shopping services
            $table->datetime('scheduled_at')->nullable();
            $table->datetime('picked_up_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}; 