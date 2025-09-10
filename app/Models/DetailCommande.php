<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailCommande extends Model
{
    use HasFactory;

    protected $table = 'details_commandes';

    protected $fillable = [
        'commande_id',
        'medicament_id',
        'quantite',
        'prix_achat',
        'sous_total',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'prix_achat' => 'decimal:2',
        'sous_total' => 'decimal:2',
    ];
    
    public function commande()
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function medicament()
    {
        return $this->belongsTo(Medicament::class, 'medicament_id');
    }
}
