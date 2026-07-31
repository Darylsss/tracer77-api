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
    Schema::table('positions', function (Blueprint $table) {
        $table->unsignedBigInteger('trackable_id')->nullable()->after('id');
        $table->string('trackable_type')->nullable()->after('trackable_id');
        $table->index(['trackable_type', 'trackable_id']);
    });
}

public function down(): void
{
    Schema::table('positions', function (Blueprint $table) {
        $table->dropColumn(['trackable_id', 'trackable_type']);
    });
}
};
