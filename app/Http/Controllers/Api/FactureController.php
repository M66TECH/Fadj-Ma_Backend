<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\Client;
use App\Models\Medicament;
use App\Models\DetailFacture;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FactureController extends Controller
{
    /**
     * Afficher la liste des factures
     */
    public function index(Request $request)
    {
        try {
            $query = Facture::with(['client', 'details.medicament']);

            // Filtrage par client
            if ($request->has('client_id')) {
                $query->where('client_id', $request->client_id);
            }

            // Filtrage par date
            if ($request->has('date_debut')) {
                $query->where('date_facture', '>=', $request->date_debut);
            }
            if ($request->has('date_fin')) {
                $query->where('date_facture', '<=', $request->date_fin);
            }

            $factures = $query->orderBy('date_facture', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Liste des factures récupérée avec succès',
                'data' => $factures
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des factures',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle facture
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
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

            // Vérifier le stock et calculer le total
            $total = 0;
            $medicaments = [];

            foreach ($request->medicaments as $item) {
                $medicament = Medicament::find($item['medicament_id']);
                
                if ($medicament->stock < $item['quantite']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour le médicament: {$medicament->nom}"
                    ], 400);
                }

                $sousTotal = $item['quantite'] * $item['prix_unitaire'];
                $total += $sousTotal;

                $medicaments[] = [
                    'medicament_id' => $item['medicament_id'],
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'sous_total' => $sousTotal
                ];
            }

            // Créer la facture
            $facture = Facture::create([
                'client_id' => $request->client_id,
                'total' => $total,
                'date_facture' => now()
            ]);

            // Créer les détails de facture et mettre à jour le stock
            foreach ($medicaments as $item) {
                DetailFacture::create([
                    'facture_id' => $facture->id,
                    'medicament_id' => $item['medicament_id'],
                    'quantite' => $item['quantite'],
                    'prix_unitaire' => $item['prix_unitaire'],
                    'sous_total' => $item['sous_total']
                ]);

                // Mettre à jour le stock
                $medicament = Medicament::find($item['medicament_id']);
                $medicament->decrement('stock', $item['quantite']);
            }

            DB::commit();

            $facture->load(['client', 'details.medicament']);

            return response()->json([
                'success' => true,
                'message' => 'Facture créée avec succès',
                'data' => $facture
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une facture spécifique
     */
    public function show(string $id)
    {
        try {
            $facture = Facture::with(['client', 'details.medicament'])->find($id);

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Facture récupérée avec succès',
                'data' => $facture
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour une facture
     */
    public function update(Request $request, string $id)
    {
        $facture = Facture::find($id);
        
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => 'Facture non trouvée'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'client_id' => 'sometimes|exists:clients,id',
            'total' => 'sometimes|numeric|min:0',
            'date_facture' => 'sometimes|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $facture->update($request->all());
            $facture->load(['client', 'details.medicament']);

            return response()->json([
                'success' => true,
                'message' => 'Facture mise à jour avec succès',
                'data' => $facture
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une facture
     */
    public function destroy(string $id)
    {
        try {
            $facture = Facture::find($id);
            
            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            DB::beginTransaction();

            // Restaurer le stock
            foreach ($facture->details as $detail) {
                $medicament = Medicament::find($detail->medicament_id);
                $medicament->increment('stock', $detail->quantite);
            }

            // Supprimer les détails
            $facture->details()->delete();

            // Supprimer la facture
            $facture->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Facture supprimée avec succès'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un PDF de facture
     */
    public function generatePdf(string $id)
    {
        try {
            $facture = Facture::with(['client', 'details.medicament'])->find($id);

            if (!$facture) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facture non trouvée'
                ], 404);
            }

            // Ici vous pouvez intégrer une librairie PDF comme DomPDF ou TCPDF
            // Pour l'instant, on retourne les données de la facture
            return response()->json([
                'success' => true,
                'message' => 'Données de facture pour génération PDF',
                'data' => $facture
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du PDF',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
