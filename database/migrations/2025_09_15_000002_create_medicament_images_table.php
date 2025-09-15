<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicament_images', function (Blueprint $table) {
            $table->id();
            $table->string('medicament_id', 50);
            $table->string('path');
            $table->boolean('is_primary')->default(false);
            $table->unsignedInteger('ordre')->default(0);
            $table->foreign('medicament_id')->references('id')->on('medicaments')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicament_images');
    }
};