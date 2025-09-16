<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicament;
use App\Models\MedicamentImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImageController extends Controller
{
    /**
     * Uploader une image pour un médicament (image principale)
     */
    public function uploadMedicamentImage(Request $request, $medicamentId)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Image invalide',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $medicament = Medicament::find($medicamentId);
            
            if (!$medicament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Médicament non trouvé'
                ], 404);
            }

            // Supprimer l'ancienne image principale si elle existe (et son enregistrement éventuel)
            if ($medicament->image_path) {
                Storage::disk('public')->delete($medicament->image_path);
            }

            // Uploader la nouvelle image
            $file = $request->file('image');
            $filename = 'medicament_' . $medicamentId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('medicaments', $filename, 'public');

            // Mettre à jour le médicament (image principale)
            $medicament->update(['image_path' => $path]);

            // Enregistrer aussi dans la table des images comme primaire
            MedicamentImage::create([
                'medicament_id' => $medicament->id,
                'path' => $path,
                'is_primary' => true,
                'ordre' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Image uploadée avec succès',
                'data' => [
                    'image_url' => Storage::url($path),
                    'image_path' => $path
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload de l\'image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer l'image principale d'un médicament
     */
    public function deleteMedicamentImage($medicamentId)
    {
        try {
            $medicament = Medicament::find($medicamentId);
            
            if (!$medicament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Médicament non trouvé'
                ], 404);
            }

            if ($medicament->image_path) {
                Storage::disk('public')->delete($medicament->image_path);
                $medicament->update(['image_path' => null]);

                // Marquer d'éventuels enregistrements comme non primaires
                MedicamentImage::where('medicament_id', $medicament->id)
                    ->where('path', $medicament->image_path)
                    ->update(['is_primary' => false]);

                return response()->json([
                    'success' => true,
                    'message' => 'Image supprimée avec succès'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Aucune image à supprimer'
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de l\'image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtenir l'URL de l'image principale d'un médicament
     */
    public function getMedicamentImage($medicamentId)
    {
        try {
            $medicament = Medicament::find($medicamentId);
            
            if (!$medicament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Médicament non trouvé'
                ], 404);
            }

            if ($medicament->image_path) {
                return response()->json([
                    'success' => true,
                    'message' => 'Image récupérée avec succès',
                    'data' => [
                        'image_url' => Storage::url($medicament->image_path),
                        'image_path' => $medicament->image_path
                    ]
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Aucune image trouvée pour ce médicament'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de l\'image',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Uploader plusieurs images pour un médicament (galerie)
     */
    public function uploadMedicamentGallery(Request $request, $medicamentId)
    {
        $validator = Validator::make($request->all(), [
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Images invalides',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $medicament = Medicament::find($medicamentId);
            
            if (!$medicament) {
                return response()->json([
                    'success' => false,
                    'message' => 'Médicament non trouvé'
                ], 404);
            }

            $uploadedImages = [];

            foreach ($request->file('images') as $index => $file) {
                $filename = 'medicament_' . $medicamentId . '_gallery_' . time() . '_' . Str::random(5) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('medicaments/gallery', $filename, 'public');

                // Persist
                MedicamentImage::create([
                    'medicament_id' => $medicament->id,
                    'path' => $path,
                    'is_primary' => false,
                    'ordre' => $index,
                ]);
                
                $uploadedImages[] = [
                    'image_url' => Storage::url($path),
                    'image_path' => $path
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Images uploadées avec succès',
                'data' => [
                    'images' => $uploadedImages
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'upload des images',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lister la galerie d'images d'un médicament
     */
    public function listMedicamentGallery($medicamentId)
    {
        $medicament = Medicament::find($medicamentId);
        if (!$medicament) {
            return response()->json([
                'success' => false,
                'message' => 'Médicament non trouvé'
            ], 404);
        }

        $images = MedicamentImage::where('medicament_id', $medicament->id)
            ->orderBy('ordre')
            ->get()
            ->map(fn($img) => [
                'id' => $img->id,
                'image_url' => Storage::url($img->path),
                'image_path' => $img->path,
                'is_primary' => $img->is_primary,
                'ordre' => $img->ordre,
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Galerie récupérée avec succès',
            'data' => [ 'images' => $images ]
        ], 200);
    }
}
