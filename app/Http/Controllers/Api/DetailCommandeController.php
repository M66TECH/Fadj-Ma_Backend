<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailCommande;
use App\Models\Commande;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DetailCommandeController extends Controller
{
    /**
     * Afficher la liste des détails de commande
     */
    public function index(Request $request)
    {
        try {
            $query = DetailCommande::with(['commande.fournisseur', 'medicament']);

            // Filtrage par commande
            if ($request->has('commande_id')) {
                $query->where('commande_id', $request->commande_id);
            }

            $details = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Liste des détails de commande récupérée avec succès',
                'data' => $details
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails de commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau détail de commande
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'commande_id' => 'required|exists:commandes,id',
            'medicament_id' => 'required|exists:medicaments,id',
            'quantite' => 'required|integer|min:1',
            'prix_unitaire' => 'required|numeric|min:0',
            'sous_total' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $detail = DetailCommande::create($request->all());
            $detail->load(['commande.fournisseur', 'medicament']);

            return response()->json([
                'success' => true,
                'message' => 'Détail de commande créé avec succès',
                'data' => $detail
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du détail de commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un détail de commande spécifique
     */
    public function show(string $id)
    {
        try {
            $detail = DetailCommande::with(['commande.fournisseur', 'medicament'])->find($id);

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Détail de commande non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Détail de commande récupéré avec succès',
                'data' => $detail
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du détail de commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un détail de commande
     */
    public function update(Request $request, string $id)
    {
        $detail = DetailCommande::find($id);
        
        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Détail de commande non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantite' => 'sometimes|integer|min:1',
            'prix_unitaire' => 'sometimes|numeric|min:0',
            'sous_total' => 'sometimes|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $detail->update($request->all());
            $detail->load(['commande.fournisseur', 'medicament']);

            return response()->json([
                'success' => true,
                'message' => 'Détail de commande mis à jour avec succès',
                'data' => $detail
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du détail de commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un détail de commande
     */
    public function destroy(string $id)
    {
        try {
            $detail = DetailCommande::find($id);
            
            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Détail de commande non trouvé'
                ], 404);
            }

            $detail->delete();

            return response()->json([
                'success' => true,
                'message' => 'Détail de commande supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du détail de commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
