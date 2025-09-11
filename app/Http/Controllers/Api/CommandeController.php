<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Fournisseur;
use App\Models\Medicament;
use App\Models\DetailCommande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    /**
     * Afficher la liste des commandes
     */
    public function index(Request $request)
    {
        try {
            $query = Commande::with(['fournisseur', 'details.medicament']);

            // Filtrage par fournisseur
            if ($request->has('fournisseur_id')) {
                $query->where('fournisseur_id', $request->fournisseur_id);
            }

            // Filtrage par date
            if ($request->has('date_debut')) {
                $query->where('date_commande', '>=', $request->date_debut);
            }
            if ($request->has('date_fin')) {
                $query->where('date_commande', '<=', $request->date_fin);
            }

            $commandes = $query->orderBy('date_commande', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Liste des commandes récupérée avec succès',
                'data' => $commandes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'medicaments' => 'required|array|min:1',
            'medicaments.*.medicament_id' => 'required|exists:medicaments,id',
            'medicaments.*.quantite' => 'required|integer|min:1',
            'medicaments.*.prix_unitaire' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Calculer le montant total
            $montantTotal = 0;
            $medicaments = [];

            foreach ($request->medicaments as $item) {
                $sousTotal = $item['quantite'] * $item['prix_unitaire'];
                $montantTotal += $sousTotal;

                $medicaments[] = [
                    'medicament_id' => $item['medicament_id'],
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'sous_total' => $sousTotal
                ];
            }

            // Créer la commande
            $commande = Commande::create([
                'fournisseur_id' => $request->fournisseur_id,
                'montant_total' => $montantTotal,
                'date_commande' => now()
            ]);

            // Créer les détails de commande
            foreach ($medicaments as $item) {
                DetailCommande::create([
                    'commande_id' => $commande->id,
                    'medicament_id' => $item['medicament_id'],
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'sous_total' => $item['sous_total']
                ]);
            }

            DB::commit();

            $commande->load(['fournisseur', 'details.medicament']);

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => $commande
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une commande spécifique
     */
    public function show(string $id)
    {
        try {
            $commande = Commande::with(['fournisseur', 'details.medicament'])->find($id);

            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Commande récupérée avec succès',
                'data' => $commande
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une commande
     */
    public function update(Request $request, string $id)
    {
        $commande = Commande::find($id);
        
        if (!$commande) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'fournisseur_id' => 'sometimes|exists:fournisseurs,id',
            'montant_total' => 'sometimes|numeric|min:0',
            'date_commande' => 'sometimes|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $commande->update($request->all());
            $commande->load(['fournisseur', 'details.medicament']);

            return response()->json([
                'success' => true,
                'message' => 'Commande mise à jour avec succès',
                'data' => $commande
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une commande
     */
    public function destroy(string $id)
    {
        try {
            $commande = Commande::find($id);
            
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            DB::beginTransaction();

            // Supprimer les détails
            $commande->details()->delete();

            // Supprimer la commande
            $commande->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande supprimée avec succès'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réceptionner une commande (mettre à jour le stock)
     */
    public function recevoir(Request $request, string $id)
    {
        try {
            $commande = Commande::with('details')->find($id);
            
            if (!$commande) {
                return response()->json([
                    'success' => false,
                    'message' => 'Commande non trouvée'
                ], 404);
            }

            DB::beginTransaction();

            // Mettre à jour le stock pour chaque médicament
            foreach ($commande->details as $detail) {
                $medicament = Medicament::find($detail->medicament_id);
                $medicament->increment('stock', $detail->quantite);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande réceptionnée avec succès. Stock mis à jour.'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réception de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
