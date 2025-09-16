<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Medicament;
use App\Models\Facture;
use App\Models\Commande;
use App\Models\Client;
use App\Models\Fournisseur;
use App\Models\DetailFacture;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Générer un rapport d'inventaire PDF avec design soigné
     */
    public function generateInventaireReport()
    {
        $medicaments = Medicament::with('groupe')
            ->orderBy('nom')
            ->get();

        $groupes = \App\Models\GroupeMedicament::withCount('medicaments')->get();
        
        $stockFaible = Medicament::where('stock', '<', 10)->with('groupe')->get();
        
        $totalValeur = $medicaments->sum(function($medicament) {
            return $medicament->stock * $medicament->prix;
        });

        $data = [
            'title' => 'Rapport d\'Inventaire',
            'subtitle' => 'Inventaire complet des médicaments',
            'date' => now()->format('d/m/Y H:i:s'),
            'medicaments' => $medicaments,
            'groupes' => $groupes,
            'stock_faible' => $stockFaible,
            'total_medicaments' => $medicaments->count(),
            'total_valeur' => $totalValeur,
            'medicaments_stock_faible' => $stockFaible->count(),
            'colors' => $this->getColorScheme()
        ];

        $html = $this->generateInventaireHTML($data);
        
        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ]);
    }

    /**
     * Générer un rapport de ventes PDF avec design soigné
     */
    public function generateVentesReport($dateDebut = null, $dateFin = null, $clientId = null)
    {
        $query = Facture::with(['client', 'details.medicament']);

        if ($dateDebut) {
            $query->where('date_facture', '>=', $dateDebut);
        }

        if ($dateFin) {
            $query->where('date_facture', '<=', $dateFin);
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $factures = $query->orderBy('date_facture', 'desc')->get();

        $totalVentes = $factures->sum('total');
        $nombreFactures = $factures->count();

        // Top médicaments vendus
        $topMedicaments = DetailFacture::whereIn('facture_id', $factures->pluck('id'))
            ->select('medicament_id', DB::raw('SUM(quantite) as quantite_vendue'), DB::raw('SUM(sous_total) as chiffre_affaires'))
            ->with('medicament:id,nom')
            ->groupBy('medicament_id')
            ->orderBy('quantite_vendue', 'desc')
            ->limit(10)
            ->get();

        $data = [
            'title' => 'Rapport de Ventes',
            'subtitle' => 'Analyse des ventes et performance',
            'date' => now()->format('d/m/Y H:i:s'),
            'periode' => [
                'debut' => $dateDebut ? Carbon::parse($dateDebut)->format('d/m/Y') : $factures->min('date_facture'),
                'fin' => $dateFin ? Carbon::parse($dateFin)->format('d/m/Y') : $factures->max('date_facture')
            ],
            'factures' => $factures,
            'total_ventes' => $totalVentes,
            'nombre_factures' => $nombreFactures,
            'top_medicaments' => $topMedicaments,
            'colors' => $this->getColorScheme()
        ];

        $html = $this->generateVentesHTML($data);
        
        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ]);
    }

    /**
     * Générer un rapport financier PDF avec design soigné
     */
    public function generateFinancierReport($mois = null, $annee = null)
    {
        $mois = $mois ?? now()->month;
        $annee = $annee ?? now()->year;

        // Revenus du mois
        $revenusMois = Facture::whereMonth('date_facture', $mois)
            ->whereYear('date_facture', $annee)
            ->sum('total');

        // Revenus par jour
        $revenusParJour = Facture::select(
            DB::raw('EXTRACT(DAY FROM date_facture) as jour'),
            DB::raw('SUM(total) as total')
        )
        ->whereMonth('date_facture', $mois)
        ->whereYear('date_facture', $annee)
        ->groupBy('jour')
        ->orderBy('jour')
        ->get();

        // Comparaison avec le mois précédent
        $moisPrecedent = $mois == 1 ? 12 : $mois - 1;
        $anneePrecedente = $mois == 1 ? $annee - 1 : $annee;
        
        $revenusMoisPrecedent = Facture::whereMonth('date_facture', $moisPrecedent)
            ->whereYear('date_facture', $anneePrecedente)
            ->sum('total');

        $evolution = $revenusMoisPrecedent > 0 
            ? (($revenusMois - $revenusMoisPrecedent) / $revenusMoisPrecedent) * 100 
            : 0;

        // Top clients
        $topClients = Facture::select('client_id', DB::raw('SUM(total) as total_achat'))
            ->whereMonth('date_facture', $mois)
            ->whereYear('date_facture', $annee)
            ->with('client:id,nom')
            ->groupBy('client_id')
            ->orderBy('total_achat', 'desc')
            ->limit(10)
            ->get();

        $data = [
            'title' => 'Rapport Financier',
            'subtitle' => 'Analyse financière et performance',
            'date' => now()->format('d/m/Y H:i:s'),
            'periode' => [
                'mois' => $mois,
                'annee' => $annee,
                'nom_mois' => $this->getMonthName($mois)
            ],
            'revenus_mois' => $revenusMois,
            'revenus_par_jour' => $revenusParJour,
            'evolution' => [
                'pourcentage' => round($evolution, 2),
                'montant' => $revenusMois - $revenusMoisPrecedent
            ],
            'top_clients' => $topClients,
            'colors' => $this->getColorScheme()
        ];

        $html = $this->generateFinancierHTML($data);
        
        return Pdf::loadHTML($html)
            ->setPaper('A4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'Arial'
            ]);
    }

    /**
     * Obtenir le schéma de couleurs basé sur les captures d'écran
     */
    private function getColorScheme()
    {
        return [
            'primary' => '#2E86AB',      // Bleu principal (sidebar)
            'secondary' => '#A23B72',    // Rose/violet secondaire
            'success' => '#F18F01',      // Orange/jaune (succès)
            'warning' => '#C73E1D',      // Rouge (avertissement)
            'info' => '#2E86AB',         // Bleu info
            'light' => '#F8F9FA',        // Gris clair
            'dark' => '#343A40',         // Gris foncé
            'white' => '#FFFFFF',        // Blanc
            'text' => '#212529',         // Texte principal
            'text_light' => '#6C757D',   // Texte secondaire
            'border' => '#DEE2E6',       // Bordures
            'background' => '#F8F9FA'    // Arrière-plan
        ];
    }

    /**
     * Générer le HTML pour le rapport d'inventaire
     */
    private function generateInventaireHTML($data)
    {
        $colors = $data['colors'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$data['title']}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.4; color: {$colors['text']}; background: {$colors['white']}; }
                
                .header { background: linear-gradient(135deg, {$colors['primary']}, {$colors['secondary']}); color: {$colors['white']}; padding: 20px; text-align: center; margin-bottom: 20px; }
                .header h1 { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                .header h2 { font-size: 16px; opacity: 0.9; }
                .header .date { font-size: 14px; margin-top: 10px; opacity: 0.8; }
                
                .summary { display: flex; justify-content: space-around; margin-bottom: 30px; background: {$colors['light']}; padding: 20px; border-radius: 8px; }
                .summary-item { text-align: center; }
                .summary-item .number { font-size: 24px; font-weight: bold; color: {$colors['primary']}; }
                .summary-item .label { font-size: 12px; color: {$colors['text_light']}; margin-top: 5px; }
                
                .section { margin-bottom: 25px; }
                .section-title { background: {$colors['primary']}; color: {$colors['white']}; padding: 10px 15px; font-size: 14px; font-weight: bold; margin-bottom: 10px; }
                
                .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .table th { background: {$colors['light']}; color: {$colors['text']}; padding: 12px 8px; text-align: left; font-weight: bold; border-bottom: 2px solid {$colors['primary']}; }
                .table td { padding: 10px 8px; border-bottom: 1px solid {$colors['border']}; }
                .table tr:nth-child(even) { background: {$colors['background']}; }
                .table tr:hover { background: {$colors['light']}; }
                
                .stock-low { color: {$colors['warning']}; font-weight: bold; }
                .stock-good { color: {$colors['success']}; }
                
                .footer { margin-top: 30px; padding: 15px; background: {$colors['light']}; text-align: center; font-size: 10px; color: {$colors['text_light']}; }
                
                .alert { padding: 15px; margin: 15px 0; border-radius: 5px; }
                .alert-warning { background: #FFF3CD; border: 1px solid #FFEAA7; color: #856404; }
                
                .grid { display: flex; flex-wrap: wrap; gap: 15px; }
                .grid-item { flex: 1; min-width: 200px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🏥 Fadj-Ma Pharmacie</h1>
                <h2>{$data['title']}</h2>
                <div class='date'>Généré le {$data['date']}</div>
            </div>
            
            <div class='summary'>
                <div class='summary-item'>
                    <div class='number'>{$data['total_medicaments']}</div>
                    <div class='label'>Médicaments Total</div>
                </div>
                <div class='summary-item'>
                    <div class='number'>{$data['medicaments_stock_faible']}</div>
                    <div class='label'>Stock Faible</div>
                </div>
                <div class='summary-item'>
                    <div class='number'>" . number_format($data['total_valeur'], 0, ',', ' ') . " FCFA</div>
                    <div class='label'>Valeur Total</div>
                </div>
                <div class='summary-item'>
                    <div class='number'>{$data['groupes']->count()}</div>
                    <div class='label'>Groupes</div>
                </div>
            </div>
            
            " . ($data['medicaments_stock_faible'] > 0 ? "
            <div class='alert alert-warning'>
                ⚠️ <strong>Attention :</strong> {$data['medicaments_stock_faible']} médicament(s) en stock faible nécessitent un réapprovisionnement.
            </div>
            " : "") . "
            
            <div class='section'>
                <div class='section-title'>📋 Inventaire Complet des Médicaments</div>
                <table class='table'>
                    <thead>
                        <tr>
                            <th>Nom du Médicament</th>
                            <th>ID</th>
                            <th>Groupe</th>
                            <th>Dosage</th>
                            <th>Prix (FCFA)</th>
                            <th>Stock</th>
                            <th>Valeur</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $this->generateMedicamentsTableRows($data['medicaments'], $colors) . "
                    </tbody>
                </table>
            </div>
            
            <div class='section'>
                <div class='section-title'>📊 Répartition par Groupe</div>
                <div class='grid'>
                    " . $this->generateGroupesCards($data['groupes'], $colors) . "
                </div>
            </div>
            
            <div class='footer'>
                <p>Rapport généré automatiquement par le système Fadj-Ma Pharmacie</p>
                <p>© " . date('Y') . " Fadj-Ma - Tous droits réservés</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Générer le HTML pour le rapport de ventes
     */
    private function generateVentesHTML($data)
    {
        $colors = $data['colors'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$data['title']}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.4; color: {$colors['text']}; background: {$colors['white']}; }
                
                .header { background: linear-gradient(135deg, {$colors['success']}, {$colors['primary']}); color: {$colors['white']}; padding: 20px; text-align: center; margin-bottom: 20px; }
                .header h1 { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                .header h2 { font-size: 16px; opacity: 0.9; }
                .header .date { font-size: 14px; margin-top: 10px; opacity: 0.8; }
                
                .summary { display: flex; justify-content: space-around; margin-bottom: 30px; background: {$colors['light']}; padding: 20px; border-radius: 8px; }
                .summary-item { text-align: center; }
                .summary-item .number { font-size: 24px; font-weight: bold; color: {$colors['success']}; }
                .summary-item .label { font-size: 12px; color: {$colors['text_light']}; margin-top: 5px; }
                
                .section { margin-bottom: 25px; }
                .section-title { background: {$colors['success']}; color: {$colors['white']}; padding: 10px 15px; font-size: 14px; font-weight: bold; margin-bottom: 10px; }
                
                .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .table th { background: {$colors['light']}; color: {$colors['text']}; padding: 12px 8px; text-align: left; font-weight: bold; border-bottom: 2px solid {$colors['success']}; }
                .table td { padding: 10px 8px; border-bottom: 1px solid {$colors['border']}; }
                .table tr:nth-child(even) { background: {$colors['background']}; }
                
                .footer { margin-top: 30px; padding: 15px; background: {$colors['light']}; text-align: center; font-size: 10px; color: {$colors['text_light']}; }
                
                .grid { display: flex; flex-wrap: wrap; gap: 15px; }
                .grid-item { flex: 1; min-width: 200px; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>💰 Fadj-Ma Pharmacie</h1>
                <h2>{$data['title']}</h2>
                <div class='date'>Période : {$data['periode']['debut']} - {$data['periode']['fin']}</div>
            </div>
            
            <div class='summary'>
                <div class='summary-item'>
                    <div class='number'>" . number_format($data['total_ventes'], 0, ',', ' ') . " FCFA</div>
                    <div class='label'>Chiffre d'Affaires</div>
                </div>
                <div class='summary-item'>
                    <div class='number'>{$data['nombre_factures']}</div>
                    <div class='label'>Factures Générées</div>
                </div>
                <div class='summary-item'>
                    <div class='number'>" . number_format($data['total_ventes'] / max($data['nombre_factures'], 1), 0, ',', ' ') . " FCFA</div>
                    <div class='label'>Panier Moyen</div>
                </div>
            </div>
            
            <div class='section'>
                <div class='section-title'>🏆 Top 10 des Médicaments les Plus Vendus</div>
                <table class='table'>
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Médicament</th>
                            <th>Quantité Vendue</th>
                            <th>Chiffre d'Affaires</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $this->generateTopMedicamentsRows($data['top_medicaments'], $colors) . "
                    </tbody>
                </table>
            </div>
            
            <div class='footer'>
                <p>Rapport généré automatiquement par le système Fadj-Ma Pharmacie</p>
                <p>© " . date('Y') . " Fadj-Ma - Tous droits réservés</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Générer le HTML pour le rapport financier
     */
    private function generateFinancierHTML($data)
    {
        $colors = $data['colors'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$data['title']}</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: 'Arial', sans-serif; font-size: 12px; line-height: 1.4; color: {$colors['text']}; background: {$colors['white']}; }
                
                .header { background: linear-gradient(135deg, {$colors['info']}, {$colors['secondary']}); color: {$colors['white']}; padding: 20px; text-align: center; margin-bottom: 20px; }
                .header h1 { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                .header h2 { font-size: 16px; opacity: 0.9; }
                .header .date { font-size: 14px; margin-top: 10px; opacity: 0.8; }
                
                .summary { display: flex; justify-content: space-around; margin-bottom: 30px; background: {$colors['light']}; padding: 20px; border-radius: 8px; }
                .summary-item { text-align: center; }
                .summary-item .number { font-size: 24px; font-weight: bold; color: {$colors['info']}; }
                .summary-item .label { font-size: 12px; color: {$colors['text_light']}; margin-top: 5px; }
                
                .section { margin-bottom: 25px; }
                .section-title { background: {$colors['info']}; color: {$colors['white']}; padding: 10px 15px; font-size: 14px; font-weight: bold; margin-bottom: 10px; }
                
                .table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .table th { background: {$colors['light']}; color: {$colors['text']}; padding: 12px 8px; text-align: left; font-weight: bold; border-bottom: 2px solid {$colors['info']}; }
                .table td { padding: 10px 8px; border-bottom: 1px solid {$colors['border']}; }
                .table tr:nth-child(even) { background: {$colors['background']}; }
                
                .footer { margin-top: 30px; padding: 15px; background: {$colors['light']}; text-align: center; font-size: 10px; color: {$colors['text_light']}; }
                
                .evolution-positive { color: {$colors['success']}; font-weight: bold; }
                .evolution-negative { color: {$colors['warning']}; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>📊 Fadj-Ma Pharmacie</h1>
                <h2>{$data['title']} - {$data['periode']['nom_mois']} {$data['periode']['annee']}</h2>
                <div class='date'>Généré le {$data['date']}</div>
            </div>
            
            <div class='summary'>
                <div class='summary-item'>
                    <div class='number'>" . number_format($data['revenus_mois'], 0, ',', ' ') . " FCFA</div>
                    <div class='label'>Revenus du Mois</div>
                </div>
                <div class='summary-item'>
                    <div class='number " . ($data['evolution']['pourcentage'] >= 0 ? 'evolution-positive' : 'evolution-negative') . "'>" . 
                        ($data['evolution']['pourcentage'] >= 0 ? '+' : '') . $data['evolution']['pourcentage'] . "%</div>
                    <div class='label'>Évolution</div>
                </div>
                <div class='summary-item'>
                    <div class='number'>" . number_format($data['revenus_mois'] / 30, 0, ',', ' ') . " FCFA</div>
                    <div class='label'>Moyenne Journalière</div>
                </div>
            </div>
            
            <div class='section'>
                <div class='section-title'>👥 Top 10 des Clients</div>
                <table class='table'>
                    <thead>
                        <tr>
                            <th>Rang</th>
                            <th>Client</th>
                            <th>Total Achat (FCFA)</th>
                        </tr>
                    </thead>
                    <tbody>
                        " . $this->generateTopClientsRows($data['top_clients'], $colors) . "
                    </tbody>
                </table>
            </div>
            
            <div class='footer'>
                <p>Rapport généré automatiquement par le système Fadj-Ma Pharmacie</p>
                <p>© " . date('Y') . " Fadj-Ma - Tous droits réservés</p>
            </div>
        </body>
        </html>";
    }

    /**
     * Générer les lignes du tableau des médicaments
     */
    private function generateMedicamentsTableRows($medicaments, $colors)
    {
        $html = '';
        foreach ($medicaments as $medicament) {
            $stockClass = $medicament->stock < 10 ? 'stock-low' : 'stock-good';
            $valeur = $medicament->stock * $medicament->prix;
            
            $html .= "
            <tr>
                <td><strong>{$medicament->nom}</strong></td>
                <td>{$medicament->id}</td>
                <td>{$medicament->groupe->nom ?? 'N/A'}</td>
                <td>{$medicament->dosage}</td>
                <td>" . number_format($medicament->prix, 0, ',', ' ') . "</td>
                <td class='{$stockClass}'>{$medicament->stock}</td>
                <td>" . number_format($valeur, 0, ',', ' ') . " FCFA</td>
            </tr>";
        }
        return $html;
    }

    /**
     * Générer les cartes des groupes
     */
    private function generateGroupesCards($groupes, $colors)
    {
        $html = '';
        foreach ($groupes as $groupe) {
            $html .= "
            <div class='grid-item' style='background: {$colors['light']}; padding: 15px; border-radius: 8px; text-align: center;'>
                <div style='font-size: 18px; font-weight: bold; color: {$colors['primary']};'>{$groupe->medicaments_count}</div>
                <div style='font-size: 12px; color: {$colors['text_light']}; margin-top: 5px;'>{$groupe->nom}</div>
            </div>";
        }
        return $html;
    }

    /**
     * Générer les lignes du top médicaments
     */
    private function generateTopMedicamentsRows($topMedicaments, $colors)
    {
        $html = '';
        $rang = 1;
        foreach ($topMedicaments as $medicament) {
            $html .= "
            <tr>
                <td><strong>#{$rang}</strong></td>
                <td>{$medicament->medicament->nom}</td>
                <td>{$medicament->quantite_vendue}</td>
                <td>" . number_format($medicament->chiffre_affaires, 0, ',', ' ') . " FCFA</td>
            </tr>";
            $rang++;
        }
        return $html;
    }

    /**
     * Générer les lignes du top clients
     */
    private function generateTopClientsRows($topClients, $colors)
    {
        $html = '';
        $rang = 1;
        foreach ($topClients as $client) {
            $html .= "
            <tr>
                <td><strong>#{$rang}</strong></td>
                <td>{$client->client->nom}</td>
                <td>" . number_format($client->total_achat, 0, ',', ' ') . " FCFA</td>
            </tr>";
            $rang++;
        }
        return $html;
    }

    /**
     * Obtenir le nom du mois en français
     */
    private function getMonthName($month)
    {
        $months = [
            1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
            5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
            9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre'
        ];
        
        return $months[$month] ?? 'mois inconnu';
    }
}
