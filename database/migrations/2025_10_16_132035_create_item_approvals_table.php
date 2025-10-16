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
        Schema::create('item_approvals', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->text('sku')->unique();
            $table->integer('status')->default(0); //0=Not Reviewed, 1=Approved, 2=Rejected
            $table->integer('user_id')->default(0);
            $table->text('reson')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_approvals');
    }
};
