<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\MedicamentController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\FactureController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\FournisseurController;
use App\Http\Controllers\Api\GroupeMedicamentController;
use App\Http\Controllers\Api\DetailFactureController;
use App\Http\Controllers\Api\DetailCommandeController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes d'authentification (publiques)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Routes d'authentification
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    
    // Routes de recherche
    Route::get('/search', [SearchController::class, 'globalSearch']);
    Route::get('/search/quick', [SearchController::class, 'quickSearch']);
    
    // Routes du dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/stats-by-period', [DashboardController::class, 'statsByPeriod']);
    Route::get('/dashboard/rapport-inventaire', [DashboardController::class, 'rapportInventaire']);
    Route::get('/dashboard/rapport-revenus', [DashboardController::class, 'rapportRevenus']);
    Route::get('/dashboard/articles-frequents', [DashboardController::class, 'articlesFrequents']);
    Route::get('/dashboard/telecharger-rapport', [DashboardController::class, 'telechargerRapport']);
    
    // Routes des rapports
    Route::get('/rapports/inventaire', [ReportController::class, 'inventairePdf']);
    Route::get('/rapports/ventes', [ReportController::class, 'ventesPdf']);
    Route::get('/rapports/commandes', [ReportController::class, 'commandesPdf']);
    Route::get('/rapports/financier', [ReportController::class, 'financierPdf']);
    
    // Routes des utilisateurs (admin seulement)
    Route::middleware(['role:admin'])->group(function () {
        Route::apiResource('users', UserController::class);
    });

    // Routes des groupes de médicaments
    Route::apiResource('groupes-medicaments', GroupeMedicamentController::class);
    Route::get('groupes-medicaments/{id}/medicaments', [GroupeMedicamentController::class, 'medicaments']);

    // Routes des médicaments (recherche avant resource pour éviter les conflits)
    Route::get('medicaments/search', [MedicamentController::class, 'search']);
    Route::apiResource('medicaments', MedicamentController::class);
    Route::patch('medicaments/{id}/stock', [MedicamentController::class, 'updateStock']);
    
    // Routes des images de médicaments
    Route::post('medicaments/{id}/image', [ImageController::class, 'uploadMedicamentImage']);
    Route::delete('medicaments/{id}/image', [ImageController::class, 'deleteMedicamentImage']);
    Route::get('medicaments/{id}/image', [ImageController::class, 'getMedicamentImage']);
    Route::post('medicaments/{id}/gallery', [ImageController::class, 'uploadMedicamentGallery']);
    Route::get('medicaments/{id}/gallery', [ImageController::class, 'listMedicamentGallery']);

    // Routes des fournisseurs
    Route::apiResource('fournisseurs', FournisseurController::class);
    Route::get('fournisseurs/{id}/commandes', [FournisseurController::class, 'commandes']);

    // Routes des clients
    Route::apiResource('clients', ClientController::class);
    Route::get('clients/{id}/factures', [ClientController::class, 'factures']);

    // Routes des commandes
    Route::apiResource('commandes', CommandeController::class);
    Route::patch('commandes/{id}/recevoir', [CommandeController::class, 'recevoir']);

    // Routes des factures
    Route::apiResource('factures', FactureController::class);
    Route::get('factures/{id}/pdf', [FactureController::class, 'generatePdf']);

    Route::apiResource('details-factures', DetailFactureController::class);   
    Route::apiResource('details-commandes', DetailCommandeController::class);

    Route::middleware(['admin'])->group(function () {
        
        Route::delete('medicaments/{id}/force', [MedicamentController::class, 'destroy']);
        Route::delete('clients/{id}/force', [ClientController::class, 'destroy']);
        Route::delete('fournisseurs/{id}/force', [FournisseurController::class, 'destroy']);
        Route::delete('factures/{id}/force', [FactureController::class, 'destroy']);
        Route::delete('commandes/{id}/force', [CommandeController::class, 'destroy']);
    });

  
    Route::get('/stats', function () {
        return response()->json([
            'success' => true,
            'message' => 'Statistiques récupérées avec succès',
            'data' => [
                'total_medicaments' => \App\Models\Medicament::count(),
                'total_clients' => \App\Models\Client::count(),
                'total_fournisseurs' => \App\Models\Fournisseur::count(),
                'total_factures' => \App\Models\Facture::count(),
                'total_commandes' => \App\Models\Commande::count(),
                'medicaments_stock_faible' => \App\Models\Medicament::where('stock', '<', 10)->count(),
                'chiffre_affaires_mois' => \App\Models\Facture::whereMonth('date_facture', now()->month)->sum('total'),
            ]
        ]);
    });
});

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API de gestion de pharmacie - Service opérationnel',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
