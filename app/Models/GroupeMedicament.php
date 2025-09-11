<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupeMedicament extends Model
{
    use HasFactory;
    
    protected $table = 'groupes_medicaments';
    
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    
    protected $fillable = [
        'id',
        'nom',
        'description',
    ];
    
    public function medicaments()
    {
        return $this->hasMany(Medicament::class, 'groupe_id');
    }
    
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($groupe) {
            if (empty($groupe->id)) {
                $groupe->id = 'GRP' . now()->format('YmdHis') . rand(100, 999);
            }
        });
    }
}
