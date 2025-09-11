<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facture extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'total',
        'date_facture',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'date_facture' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function details()
    {
        return $this->hasMany(DetailFacture::class, 'facture_id');
    }
}
