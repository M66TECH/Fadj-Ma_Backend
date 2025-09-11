<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Générer un rapport d'inventaire PDF
     */
    public function inventairePdf(Request $request)
    {
        try {
            $pdf = $this->reportService->generateInventaireReport();
            
            return $pdf->download('rapport-inventaire-' . now()->format('Y-m-d-H-i-s') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport d\'inventaire',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un rapport de ventes PDF
     */
    public function ventesPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after_or_equal:date_debut',
            'client_id' => 'sometimes|exists:clients,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pdf = $this->reportService->generateVentesReport(
                $request->get('date_debut'),
                $request->get('date_fin'),
                $request->get('client_id')
            );
            
            return $pdf->download('rapport-ventes-' . now()->format('Y-m-d-H-i-s') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport de ventes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un rapport de commandes PDF
     */
    public function commandesPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after_or_equal:date_debut',
            'fournisseur_id' => 'sometimes|exists:fournisseurs,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Pour l'instant, on retourne un message car le service n'est pas encore implémenté
            return response()->json([
                'success' => true,
                'message' => 'Rapport de commandes en cours de développement',
                'data' => [
                    'date_debut' => $request->get('date_debut'),
                    'date_fin' => $request->get('date_fin'),
                    'fournisseur_id' => $request->get('fournisseur_id')
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport de commandes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Générer un rapport financier PDF
     */
    public function financierPdf(Request $request)
    {
        $validator = Validator::make($request->all(), [
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
            $pdf = $this->reportService->generateFinancierReport(
                $request->get('mois'),
                $request->get('annee')
            );
            
            return $pdf->download('rapport-financier-' . now()->format('Y-m-d-H-i-s') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport financier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser un rapport d'inventaire (sans téléchargement)
     */
    public function previewInventaire(Request $request)
    {
        try {
            $pdf = $this->reportService->generateInventaireReport();
            
            return $pdf->stream('rapport-inventaire-preview.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prévisualisation du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser un rapport de ventes
     */
    public function previewVentes(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_debut' => 'sometimes|date',
            'date_fin' => 'sometimes|date|after_or_equal:date_debut',
            'client_id' => 'sometimes|exists:clients,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètres invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pdf = $this->reportService->generateVentesReport(
                $request->get('date_debut'),
                $request->get('date_fin'),
                $request->get('client_id')
            );
            
            return $pdf->stream('rapport-ventes-preview.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prévisualisation du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prévisualiser un rapport financier
     */
    public function previewFinancier(Request $request)
    {
        $validator = Validator::make($request->all(), [
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
            $pdf = $this->reportService->generateFinancierReport(
                $request->get('mois'),
                $request->get('annee')
            );
            
            return $pdf->stream('rapport-financier-preview.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la prévisualisation du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
