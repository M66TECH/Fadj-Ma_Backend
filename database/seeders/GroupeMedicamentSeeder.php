<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GroupeMedicament;

class GroupeMedicamentSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['nom' => 'Non classé', 'description' => 'Groupe par défaut'],
[
    ['nom' => 'Non classé', 'description' => 'Groupe par défaut'],
    ['nom' => 'Antibiotiques', 'description' => null],
    ['nom' => 'Antalgiques', 'description' => null],
    ['nom' => 'Anti-inflammatoires', 'description' => null],
    ['nom' => 'Vitamines', 'description' => null],
    ['nom' => 'Antifongiques', 'description' => 'Utilisés pour traiter les infections fongiques'],
    ['nom' => 'Antiviraux', 'description' => 'Utilisés contre les infections virales'],
    ['nom' => 'Antihypertenseurs', 'description' => 'Traitement de l’hypertension artérielle'],
    ['nom' => 'Antidiabétiques', 'description' => 'Régulation du taux de glucose sanguin'],
    ['nom' => 'Antispasmodiques', 'description' => 'Réduction des spasmes musculaires'],
    ['nom' => 'Antihistaminiques', 'description' => 'Traitement des réactions allergiques'],
    ['nom' => 'Antipsychotiques', 'description' => 'Utilisés en psychiatrie pour les troubles mentaux'],
    ['nom' => 'Antidépresseurs', 'description' => 'Traitement des troubles dépressifs'],
    ['nom' => 'Anesthésiques', 'description' => 'Induction de l’insensibilité à la douleur'],
    ['nom' => 'Anticoagulants', 'description' => 'Prévention des caillots sanguins'],
    ['nom' => 'Diurétiques', 'description' => 'Favorisent l’élimination de l’eau par les reins'],
    ['nom' => 'Corticoïdes', 'description' => 'Anti-inflammatoires puissants à base de cortisone'],
    ['nom' => 'Immunosuppresseurs', 'description' => 'Réduction de l’activité du système immunitaire'],
    ['nom' => 'Antiparasitaires', 'description' => 'Traitement des infections parasitaires'],
    ['nom' => 'Anticonvulsivants', 'description' => 'Prévention des crises d’épilepsie'],
]

        ];

        foreach ($groups as $g) {
            GroupeMedicament::firstOrCreate(
                ['nom' => $g['nom']],
                ['description' => $g['description'] ?? null]
            );
        }
    }
}