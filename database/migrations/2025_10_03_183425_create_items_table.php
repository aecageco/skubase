<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('sku');
            $table->string('display_name')->nullable();
            $table->string('detailed_description')->nullable();
            $table->string('url')->nullable();
            $table->string('upc')->nullable();
            $table->string('short_description')->nullable();
            $table->string('new_short_description')->nullable();
            $table->string('new_long_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
