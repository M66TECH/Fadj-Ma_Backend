<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    

    protected $fillable = [
        'nom',
        'adresse',
        'telephone',
        'email',
    ];
    

    public function factures()
    {
        return $this->hasMany(Facture::class, 'client_id');
    }
    
}
