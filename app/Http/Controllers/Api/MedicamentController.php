<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicament;
use App\Models\GroupeMedicament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MedicamentController extends Controller
{
    /**
     * Afficher la liste des médicaments
     */
    public function index(Request $request)
    {
        try {
            $query = Medicament::with('groupe');

            // Recherche par nom
            if ($request->has('search')) {
                $query->where('nom', 'like', '%' . $request->search . '%');
            }

            // Filtrage par groupe
            if ($request->has('groupe_id')) {
                $query->where('groupe_id', $request->groupe_id);
            }

            // Filtrage par stock faible
            if ($request->has('stock_faible') && $request->stock_faible) {
                $query->where('stock', '<', 10);
            }

            $medicaments = $query->orderBy('nom')->get();

            return response()->json([
                'success' => true,
                'message' => 'Liste des médicaments récupérée avec succès',
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

    /**
     * Créer un nouveau médicament
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'dosage' => 'required|string|max:100',
            'prix' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'groupe_id' => 'required|integer|exists:groupes_medicaments,id',
            // Champs détaillés
            'composition' => 'nullable|string',
            'fabricant' => 'nullable|string',
            'type_consommation' => 'nullable|string',
            'date_expiration' => 'nullable|date',
            'description_detaillee' => 'nullable|string',
            'dosage_posologie' => 'nullable|string',
            'ingredients_actifs' => 'nullable|string',
            'effets_secondaires' => 'nullable|string',
            'forme_pharmaceutique' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $medicament = Medicament::create($request->all());
            $medicament->load('groupe');

            return response()->json([
                'success' => true,
                'message' => 'Médicament créé avec succès',
                'data' => $medicament
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du médicament',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un médicament spécifique
     */
    public function show(string $id)
    {
        try {
            $medicament = Medicament::with(['groupe', 'images'])->find($id);

            if (!$medicament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Médicament non trouvé'
                ], 404);
            }

            $data = $medicament->toArray();
            $data['gallery'] = $medicament->images->map(fn($i) => asset('storage/' . $i->path));

            return response()->json([
                'success' => true,
                'message' => 'Médicament récupéré avec succès',
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du médicament',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour un médicament
     */
    public function update(Request $request, string $id)
    {
        $medicament = Medicament::find($id);
        
        if (!$medicament) {
            return response()->json([
                'success' => false,
                'message' => 'Médicament non trouvé'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'dosage' => 'sometimes|string|max:100',
            'prix' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'groupe_id' => 'sometimes|integer|exists:groupes_medicaments,id',
            // Champs détaillés
            'composition' => 'nullable|string',
            'fabricant' => 'nullable|string',
            'type_consommation' => 'nullable|string',
            'date_expiration' => 'nullable|date',
            'description_detaillee' => 'nullable|string',
            'dosage_posologie' => 'nullable|string',
            'ingredients_actifs' => 'nullable|string',
            'effets_secondaires' => 'nullable|string',
            'forme_pharmaceutique' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $medicament->update($request->all());
            $medicament->load('groupe');

            return response()->json([
                'success' => true,
                'message' => 'Médicament mis à jour avec succès',
                'data' => $medicament
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du médicament',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer un médicament
     */
    public function destroy(string $id)
    {
        try {
            $medicament = Medicament::find($id);
            
            if (!$medicament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Médicament non trouvé'
                ], 404);
            }

            $medicament->delete();

            return response()->json([
                'success' => true,
                'message' => 'Médicament supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du médicament',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mettre à jour le stock d'un médicament
     */
    public function updateStock(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'stock' => 'required|integer|min:0',
            'operation' => 'required|in:ajout,retrait,definir'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Données invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $medicament = Medicament::find($id);
            
            if (!$medicament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Médicament non trouvé'
                ], 404);
            }

            $nouveauStock = $medicament->stock;
            
            switch ($request->operation) {
                case 'ajout':
                    $nouveauStock += $request->stock;
                    break;
                case 'retrait':
                    $nouveauStock -= $request->stock;
                    if ($nouveauStock < 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Stock insuffisant'
                        ], 400);
                    }
                    break;
                case 'definir':
                    $nouveauStock = $request->stock;
                    break;
            }

            $medicament->update(['stock' => $nouveauStock]);
            $medicament->load('groupe');

            return response()->json([
                'success' => true,
                'message' => 'Stock mis à jour avec succès',
                'data' => $medicament
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechercher des médicaments par nom
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:2'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Terme de recherche requis (minimum 2 caractères)',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $medicaments = Medicament::with('groupe')
                ->where('nom', 'like', '%' . $request->q . '%')
                ->orWhere('description', 'like', '%' . $request->q . '%')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Recherche effectuée avec succès',
                'data' => $medicaments
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la recherche',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
