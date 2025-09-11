<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupeMedicament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GroupeMedicamentController extends Controller
{
    /**
     * Afficher la liste des groupes de médicaments
     */
    public function index(Request $request)
    {
        try {
            $query = GroupeMedicament::withCount('medicaments');

            // Recherche par nom
            if ($request->has('search')) {
                $query->where('nom', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
            }

            $groupes = $query->orderBy('nom')->get();

            return response()->json([
                'success' => true,
                'message' => 'Liste des groupes de médicaments récupérée avec succès',
                'data' => $groupes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des groupes de médicaments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau groupe de médicaments
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255|unique:groupes_medicaments,nom',
            'description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $groupe = GroupeMedicament::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Groupe de médicaments créé avec succès',
                'data' => $groupe
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du groupe de médicaments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un groupe de médicaments spécifique
     */
    public function show(string $id)
    {
        try {
            $groupe = GroupeMedicament::with('medicaments')->find($id);

            if (!$groupe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Groupe de médicaments non trouvé'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Groupe de médicaments récupéré avec succès',
                'data' => $groupe
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du groupe de médicaments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un groupe de médicaments
     */
    public function update(Request $request, string $id)
    {
        $groupe = GroupeMedicament::find($id);
        
        if (!$groupe) {
            return response()->json([
                'success' => false,
                'message' => 'Groupe de médicaments non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255|unique:groupes_medicaments,nom,' . $id,
            'description' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $groupe->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Groupe de médicaments mis à jour avec succès',
                'data' => $groupe
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du groupe de médicaments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un groupe de médicaments
     */
    public function destroy(string $id)
    {
        try {
            $groupe = GroupeMedicament::find($id);
            
            if (!$groupe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Groupe de médicaments non trouvé'
                ], 404);
            }

            // Vérifier s'il y a des médicaments dans ce groupe
            if ($groupe->medicaments()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer ce groupe car il contient des médicaments'
                ], 400);
            }

            $groupe->delete();

            return response()->json([
                'success' => true,
                'message' => 'Groupe de médicaments supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du groupe de médicaments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les médicaments d'un groupe
     */
    public function medicaments(string $id)
    {
        try {
            $groupe = GroupeMedicament::find($id);
            
            if (!$groupe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Groupe de médicaments non trouvé'
                ], 404);
            }

            $medicaments = $groupe->medicaments()->get();

            return response()->json([
                'success' => true,
                'message' => 'Médicaments du groupe récupérés avec succès',
                'data' => $medicaments
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des médicaments',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
