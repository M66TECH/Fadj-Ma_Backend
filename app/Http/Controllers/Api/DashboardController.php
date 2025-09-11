<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicament;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\Facture;
use App\Models\Commande;
use App\Models\DetailFacture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Obtenir les statistiques générales du dashboard
     */
    public function index()
    {
        try {
            $currentMonth = now()->month;
            $currentYear = now()->year;
            
            // Statistiques de base
            $totalMedicaments = Medicament::count();
            $totalClients = Client::count();
            $totalFournisseurs = Fournisseur::count();
            $totalFactures = Facture::count();
            $totalCommandes = Commande::count();
            
            // Médicaments en stock faible
            $medicamentsStockFaible = Medicament::where('stock', '<', 10)->count();
            
            // Chiffre d'affaires du mois
            $chiffreAffairesMois = Facture::whereMonth('date_facture', $currentMonth)
                ->whereYear('date_facture', $currentYear)
                ->sum('total');
            
            // Nombre de groupes de médicaments
            $totalGroupes = \App\Models\GroupeMedicament::count();
            
            // Quantité de médicaments vendus ce mois
            $quantiteMedicamentsVendus = DetailFacture::whereHas('facture', function($query) use ($currentMonth, $currentYear) {
                $query->whereMonth('date_facture', $currentMonth)
                      ->whereYear('date_facture', $currentYear);
            })->sum('quantite');
            
            // Articles fréquemment vendus
            $articleFrequent = DetailFacture::select('medicament_id', DB::raw('SUM(quantite) as total_vendu'))
                ->whereHas('facture', function($query) use ($currentMonth, $currentYear) {
                    $query->whereMonth('date_facture', $currentMonth)
                          ->whereYear('date_facture', $currentYear);
                })
                ->with('medicament:id,nom')
                ->groupBy('medicament_id')
                ->orderBy('total_vendu', 'desc')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Statistiques du dashboard récupérées avec succès',
                'data' => [
                    'kpis' => [
                        'statut_inventaire' => [
                            'status' => $medicamentsStockFaible > 0 ? 'warning' : 'good',
                            'message' => $medicamentsStockFaible > 0 ? 'Attention' : 'Bien',
                            'count' => $medicamentsStockFaible
                        ],
                        'revenu_mois' => [
                            'montant' => number_format($chiffreAffairesMois, 0, ',', '.') . ' FCFA',
                            'mois' => $this->getMonthName($currentMonth),
                            'annee' => $currentYear
                        ],
                        'medicaments_disponibles' => [
                            'total' => $totalMedicaments
                        ],
                        'penurie_medicaments' => [
                            'count' => $medicamentsStockFaible
                        ]
                    ],
                    'inventaire' => [
                        'total_medicaments' => $totalMedicaments,
                        'groupes_medecine' => $totalGroupes
                    ],
                    'rapport_rapide' => [
                        'mois' => $this->getMonthName($currentMonth),
                        'annee' => $currentYear,
                        'quantite_medicaments_vendus' => $quantiteMedicamentsVendus,
                        'factures_generees' => Facture::whereMonth('date_facture', $currentMonth)
                            ->whereYear('date_facture', $currentYear)
                            ->count()
                    ],
                    'pharmacie' => [
                        'total_fournisseurs' => $totalFournisseurs,
                        'total_utilisateurs' => \App\Models\User::count()
                    ],
                    'clients' => [
                        'total_clients' => $totalClients,
                        'article_frequent' => $articleFrequent ? $articleFrequent->medicament->nom : 'Aucun'
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques par période
     */
    public function statsByPeriod(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'mois' => 'sometimes|integer|between:1,12',
            'annee' => 'sometimes|integer|min:2020|max:2030',
            'periode' => 'sometimes|in:jour,semaine,mois,annee'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $mois = $request->get('mois', now()->month);
            $annee = $request->get('annee', now()->year);
            $periode = $request->get('periode', 'mois');

            $query = Facture::query();

            switch ($periode) {
                case 'jour':
                    $query->whereDate('date_facture', now()->toDateString());
                    break;
                case 'semaine':
                    $query->whereBetween('date_facture', [
                        now()->startOfWeek(),
                        now()->endOfWeek()
                    ]);
                    break;
                case 'mois':
                    $query->whereMonth('date_facture', $mois)
                          ->whereYear('date_facture', $annee);
                    break;
                case 'annee':
                    $query->whereYear('date_facture', $annee);
                    break;
            }

            $chiffreAffaires = $query->sum('total');
            $nombreFactures = $query->count();

            // Quantité de médicaments vendus
            $quantiteVendue = DetailFacture::whereHas('facture', function($q) use ($query) {
                $q->whereIn('id', $query->select('id'));
            })->sum('quantite');

            return response()->json([
                'success' => true,
                'message' => 'Statistiques par période récupérées avec succès',
                'data' => [
                    'periode' => $periode,
                    'mois' => $mois,
                    'annee' => $annee,
                    'chiffre_affaires' => number_format($chiffreAffaires, 0, ',', '.') . ' FCFA',
                    'nombre_factures' => $nombreFactures,
                    'quantite_medicaments_vendus' => $quantiteVendue
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques par période',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le rapport détaillé de l'inventaire
     */
    public function rapportInventaire()
    {
        try {
            $medicamentsStockFaible = Medicament::where('stock', '<', 10)
                ->with('groupe')
                ->get();

            $medicamentsParGroupe = Medicament::select('groupe_id', DB::raw('COUNT(*) as count'))
                ->with('groupe:id,nom')
                ->groupBy('groupe_id')
                ->get();

            $totalValeurStock = Medicament::sum(DB::raw('stock * prix'));

            return response()->json([
                'success' => true,
                'message' => 'Rapport d\'inventaire récupéré avec succès',
                'data' => [
                    'medicaments_stock_faible' => $medicamentsStockFaible,
                    'medicaments_par_groupe' => $medicamentsParGroupe,
                    'total_valeur_stock' => number_format($totalValeurStock, 0, ',', '.') . ' FCFA',
                    'total_medicaments' => Medicament::count(),
                    'groupes_medicaments' => \App\Models\GroupeMedicament::count()
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du rapport d\'inventaire',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le rapport détaillé des revenus
     */
    public function rapportRevenus(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'mois' => 'sometimes|integer|between:1,12',
            'annee' => 'sometimes|integer|min:2020|max:2030'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $mois = $request->get('mois', now()->month);
            $annee = $request->get('annee', now()->year);

            // Revenus par jour du mois
            $revenusParJour = Facture::select(
                DB::raw('DAY(date_facture) as jour'),
                DB::raw('SUM(total) as total')
            )
            ->whereMonth('date_facture', $mois)
            ->whereYear('date_facture', $annee)
            ->groupBy('jour')
            ->orderBy('jour')
            ->get();

            // Top 10 des médicaments les plus vendus
            $topMedicaments = DetailFacture::select(
                'medicament_id',
                DB::raw('SUM(quantite) as quantite_vendue'),
                DB::raw('SUM(sous_total) as chiffre_affaires')
            )
            ->whereHas('facture', function($query) use ($mois, $annee) {
                $query->whereMonth('date_facture', $mois)
                      ->whereYear('date_facture', $annee);
            })
            ->with('medicament:id,nom,prix')
            ->groupBy('medicament_id')
            ->orderBy('quantite_vendue', 'desc')
            ->limit(10)
            ->get();

            // Revenus totaux du mois
            $revenusTotaux = Facture::whereMonth('date_facture', $mois)
                ->whereYear('date_facture', $annee)
                ->sum('total');

            // Comparaison avec le mois précédent
            $moisPrecedent = $mois == 1 ? 12 : $mois - 1;
            $anneePrecedente = $mois == 1 ? $annee - 1 : $annee;
            
            $revenusMoisPrecedent = Facture::whereMonth('date_facture', $moisPrecedent)
                ->whereYear('date_facture', $anneePrecedente)
                ->sum('total');

            $evolution = $revenusMoisPrecedent > 0 
                ? (($revenusTotaux - $revenusMoisPrecedent) / $revenusMoisPrecedent) * 100 
                : 0;

            return response()->json([
                'success' => true,
                'message' => 'Rapport de revenus récupéré avec succès',
                'data' => [
                    'periode' => [
                        'mois' => $mois,
                        'annee' => $annee,
                        'nom_mois' => $this->getMonthName($mois)
                    ],
                    'revenus_totaux' => number_format($revenusTotaux, 0, ',', '.') . ' FCFA',
                    'revenus_par_jour' => $revenusParJour,
                    'top_medicaments' => $topMedicaments,
                    'evolution' => [
                        'pourcentage' => round($evolution, 2),
                        'montant' => $revenusTotaux - $revenusMoisPrecedent
                    ]
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du rapport de revenus',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir les articles fréquemment vendus
     */
    public function articlesFrequents(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'limit' => 'sometimes|integer|min:1|max:50',
            'periode' => 'sometimes|in:jour,semaine,mois,annee'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $limit = $request->get('limit', 10);
            $periode = $request->get('periode', 'mois');

            $query = DetailFacture::query();

            switch ($periode) {
                case 'jour':
                    $query->whereHas('facture', function($q) {
                        $q->whereDate('date_facture', now()->toDateString());
                    });
                    break;
                case 'semaine':
                    $query->whereHas('facture', function($q) {
                        $q->whereBetween('date_facture', [
                            now()->startOfWeek(),
                            now()->endOfWeek()
                        ]);
                    });
                    break;
                case 'mois':
                    $query->whereHas('facture', function($q) {
                        $q->whereMonth('date_facture', now()->month)
                          ->whereYear('date_facture', now()->year);
                    });
                    break;
                case 'annee':
                    $query->whereHas('facture', function($q) {
                        $q->whereYear('date_facture', now()->year);
                    });
                    break;
            }

            $articles = $query->select(
                'medicament_id',
                DB::raw('SUM(quantite) as quantite_vendue'),
                DB::raw('SUM(sous_total) as chiffre_affaires'),
                DB::raw('COUNT(DISTINCT facture_id) as nombre_ventes')
            )
            ->with('medicament:id,nom,prix,stock')
            ->groupBy('medicament_id')
            ->orderBy('quantite_vendue', 'desc')
            ->limit($limit)
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Articles fréquents récupérés avec succès',
                'data' => [
                    'periode' => $periode,
                    'articles' => $articles
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des articles fréquents',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Télécharger le rapport complet
     */
    public function telechargerRapport(Request $request)
    {
        try {
            // Ici vous pouvez intégrer une librairie PDF comme DomPDF
            // Pour l'instant, on retourne les données du rapport
            
            $rapportData = [
                'date_generation' => now()->format('d/m/Y H:i:s'),
                'statistiques' => $this->index()->getData()->data,
                'rapport_inventaire' => $this->rapportInventaire()->getData()->data,
                'rapport_revenus' => $this->rapportRevenus($request)->getData()->data
            ];

            return response()->json([
                'success' => true,
                'message' => 'Rapport généré avec succès',
                'data' => $rapportData
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir le nom du mois en français
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
        ];
        
        return $months[$month] ?? 'mois inconnu';
    }
}
