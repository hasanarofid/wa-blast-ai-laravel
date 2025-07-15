<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->decimal('price_per_km', 10, 2)->default(0);
            $table->decimal('minimum_price', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable(); // Additional settings
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('services');
    }
}; 