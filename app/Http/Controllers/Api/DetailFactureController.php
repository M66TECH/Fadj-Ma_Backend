<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailFacture;
use App\Models\Facture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DetailFactureController extends Controller
{
    /**
     * Afficher la liste des détails de facture
     */
    public function index(Request $request)
    {
        try {
            $query = DetailFacture::with(['facture.client', 'medicament']);

            // Filtrage par facture
            if ($request->has('facture_id')) {
                $query->where('facture_id', $request->facture_id);
            }

            $details = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Liste des détails de facture récupérée avec succès',
                'data' => $details
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails de facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau détail de facture
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'facture_id' => 'required|exists:factures,id',
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
            $detail = DetailFacture::create($request->all());
            $detail->load(['facture.client', 'medicament']);

            return response()->json([
                'success' => true,
                'message' => 'Détail de facture créé avec succès',
                'data' => $detail
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du détail de facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un détail de facture spécifique
     */
    public function show(string $id)
    {
        try {
            $detail = DetailFacture::with(['facture.client', 'medicament'])->find($id);

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Détail de facture non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Détail de facture récupéré avec succès',
                'data' => $detail
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du détail de facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un détail de facture
     */
    public function update(Request $request, string $id)
    {
        $detail = DetailFacture::find($id);
        
        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'Détail de facture non trouvé'
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
            $detail->load(['facture.client', 'medicament']);

            return response()->json([
                'success' => true,
                'message' => 'Détail de facture mis à jour avec succès',
                'data' => $detail
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du détail de facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un détail de facture
     */
    public function destroy(string $id)
    {
        try {
            $detail = DetailFacture::find($id);
            
            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Détail de facture non trouvé'
                ], 404);
            }

            $detail->delete();

            return response()->json([
                'success' => true,
                'message' => 'Détail de facture supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du détail de facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
