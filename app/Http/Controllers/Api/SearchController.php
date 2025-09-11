<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicament;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\Facture;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SearchController extends Controller
{
    /**
     * Recherche globale dans toute l'application
     */
    public function globalSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2',
            'type' => 'sometimes|in:all,medicaments,clients,fournisseurs,factures,commandes'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres de recherche invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = $request->q;
            $type = $request->get('type', 'all');
            $results = [];

            // Recherche dans les médicaments
            if ($type === 'all' || $type === 'medicaments') {
                $medicaments = Medicament::where('nom', 'like', '%' . $query . '%')
                    ->orWhere('description', 'like', '%' . $query . '%')
                    ->orWhere('dosage', 'like', '%' . $query . '%')
                    ->with('groupe')
                    ->limit(10)
                    ->get();

                $results['medicaments'] = [
                    'count' => $medicaments->count(),
                    'data' => $medicaments
                ];
            }

            // Recherche dans les clients
            if ($type === 'all' || $type === 'clients') {
                $clients = Client::where('nom', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('telephone', 'like', '%' . $query . '%')
                    ->limit(10)
                    ->get();

                $results['clients'] = [
                    'count' => $clients->count(),
                    'data' => $clients
                ];
            }

            // Recherche dans les fournisseurs
            if ($type === 'all' || $type === 'fournisseurs') {
                $fournisseurs = Fournisseur::where('nom', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('telephone', 'like', '%' . $query . '%')
                    ->limit(10)
                    ->get();

                $results['fournisseurs'] = [
                    'count' => $fournisseurs->count(),
                    'data' => $fournisseurs
                ];
            }

            // Recherche dans les factures
            if ($type === 'all' || $type === 'factures') {
                $factures = Facture::whereHas('client', function($q) use ($query) {
                    $q->where('nom', 'like', '%' . $query . '%');
                })
                ->orWhere('id', 'like', '%' . $query . '%')
                ->with(['client:id,nom'])
                ->limit(10)
                ->get();

                $results['factures'] = [
                    'count' => $factures->count(),
                    'data' => $factures
                ];
            }

            // Recherche dans les commandes
            if ($type === 'all' || $type === 'commandes') {
                $commandes = Commande::whereHas('fournisseur', function($q) use ($query) {
                    $q->where('nom', 'like', '%' . $query . '%');
                })
                ->orWhere('id', 'like', '%' . $query . '%')
                ->with(['fournisseur:id,nom'])
                ->limit(10)
                ->get();

                $results['commandes'] = [
                    'count' => $commandes->count(),
                    'data' => $commandes
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Recherche effectuée avec succès',
                'data' => [
                    'query' => $query,
                    'type' => $type,
                    'results' => $results,
                    'total_results' => array_sum(array_column($results, 'count'))
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Recherche rapide pour l'autocomplétion
     */
    public function quickSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètre de recherche requis',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $query = $request->q;
            $results = [];

            // Recherche rapide dans les médicaments (priorité)
            $medicaments = Medicament::where('nom', 'like', '%' . $query . '%')
                ->select('id', 'nom', 'prix', 'stock')
                ->limit(5)
                ->get();

            foreach ($medicaments as $medicament) {
                $results[] = [
                    'type' => 'medicament',
                    'id' => $medicament->id,
                    'nom' => $medicament->nom,
                    'prix' => $medicament->prix,
                    'stock' => $medicament->stock,
                    'url' => '/medicaments/' . $medicament->id
                ];
            }

            // Recherche rapide dans les clients
            $clients = Client::where('nom', 'like', '%' . $query . '%')
                ->select('id', 'nom', 'email')
                ->limit(3)
                ->get();

            foreach ($clients as $client) {
                $results[] = [
                    'type' => 'client',
                    'id' => $client->id,
                    'nom' => $client->nom,
                    'email' => $client->email,
                    'url' => '/clients/' . $client->id
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Recherche rapide effectuée avec succès',
                'data' => [
                    'query' => $query,
                    'results' => $results
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche rapide',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
