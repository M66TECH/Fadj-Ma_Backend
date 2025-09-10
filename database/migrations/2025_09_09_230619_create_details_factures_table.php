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
        Schema::create('details_factures', function (Blueprint $table) {
            $table->id();
            $table->string('facture_id', 20);
            $table->string('medicament_id', 20);
            $table->integer('quantite');
            $table->decimal('prix_unitaire', 10, 2);
            $table->decimal('sous_total', 12, 2);
            $table->foreign('facture_id')->references('id')->on('factures')->cascadeOnDelete();
            $table->foreign('medicament_id')->references('id')->on('medicaments')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('details_factures');
    }
};
