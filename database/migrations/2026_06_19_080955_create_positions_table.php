<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->decimal('lat',       10, 6);
            $table->decimal('lng',       10, 6);
            $table->float('vitesse');
            $table->float('direction');
            $table->integer('satellites');
            $table->float('batterie');
            $table->tinyInteger('sos')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};