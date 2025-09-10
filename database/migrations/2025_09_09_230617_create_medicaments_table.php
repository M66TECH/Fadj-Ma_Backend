<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('medicaments', function (Blueprint $table) {
            $table->string('id', 20)->primary();
            $table->string('nom', 150);
            $table->text('description')->nullable();
            $table->string('dosage', 50)->nullable();
            $table->decimal('prix', 10, 2);
            $table->integer('stock')->default(0);
            $table->unsignedBigInteger('groupe_id')->nullable();
            $table->foreign('groupe_id')->references('id')->on('groupes_medicaments')->nullOnDelete();
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('medicaments');
    }
};
