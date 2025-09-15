<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicaments', function (Blueprint $table) {
            $table->text('composition')->nullable()->after('dosage');
            $table->string('fabricant')->nullable()->after('composition');
            $table->string('type_consommation')->nullable()->after('fabricant');
            $table->date('date_expiration')->nullable()->after('type_consommation');
            $table->longText('description_detaillee')->nullable()->after('description');
            $table->longText('dosage_posologie')->nullable()->after('description_detaillee');
            $table->longText('ingredients_actifs')->nullable()->after('dosage_posologie');
            $table->longText('effets_secondaires')->nullable()->after('ingredients_actifs');
            $table->string('forme_pharmaceutique')->nullable()->after('effets_secondaires');
        });
    }

    public function down(): void
    {
        Schema::table('medicaments', function (Blueprint $table) {
            $table->dropColumn([
                'composition',
                'fabricant',
                'type_consommation',
                'date_expiration',
                'description_detaillee',
                'dosage_posologie',
                'ingredients_actifs',
                'effets_secondaires',
                'forme_pharmaceutique',
            ]);
        });
    }
};