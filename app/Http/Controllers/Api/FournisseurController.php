<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fournisseur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FournisseurController extends Controller
{
    /**
     * Afficher la liste des fournisseurs
     */
    public function index(Request $request)
    {
        try {
            $query = Fournisseur::query();

            // Recherche par nom
            if ($request->has('search')) {
                $query->where('nom', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
            }

            $fournisseurs = $query->orderBy('nom')->get();

            return response()->json([
                'success' => true,
                'message' => 'Liste des fournisseurs récupérée avec succès',
                'data' => $fournisseurs
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des fournisseurs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau fournisseur
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'adresse' => 'nullable|string|max:500',
            'telephone' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:fournisseurs,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fournisseur = Fournisseur::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur créé avec succès',
                'data' => $fournisseur
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un fournisseur spécifique
     */
    public function show(string $id)
    {
        try {
            $fournisseur = Fournisseur::find($id);

            if (!$fournisseur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fournisseur non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur récupéré avec succès',
                'data' => $fournisseur
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un fournisseur
     */
    public function update(Request $request, string $id)
    {
        $fournisseur = Fournisseur::find($id);
        
        if (!$fournisseur) {
            return response()->json([
                'success' => false,
                'message' => 'Fournisseur non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'adresse' => 'nullable|string|max:500',
            'telephone' => 'nullable|string|max:20',
            'email' => 'sometimes|email|unique:fournisseurs,email,' . $id
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $fournisseur->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur mis à jour avec succès',
                'data' => $fournisseur
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un fournisseur
     */
    public function destroy(string $id)
    {
        try {
            $fournisseur = Fournisseur::find($id);
            
            if (!$fournisseur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fournisseur non trouvé'
                ], 404);
            }

            $fournisseur->delete();

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du fournisseur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les commandes d'un fournisseur
     */
    public function commandes(string $id)
    {
        try {
            $fournisseur = Fournisseur::find($id);
            
            if (!$fournisseur) {
                return response()->json([
                    'success' => false,
                    'message' => 'Fournisseur non trouvé'
                ], 404);
            }

            $commandes = $fournisseur->commandes()->with('details.medicament')->get();

            return response()->json([
                'success' => true,
                'message' => 'Commandes du fournisseur récupérées avec succès',
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
}
