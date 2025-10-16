<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('item_approvals', function (Blueprint $table) {
            $table->renameColumn('reson', 'reason');
        });
    }

    public function down(): void
    {
        Schema::table('item_approvals', function (Blueprint $table) {
            $table->renameColumn('reason', 'reson');
        });
    }
};
