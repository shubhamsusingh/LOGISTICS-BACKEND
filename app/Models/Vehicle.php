<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    use HasFactory;

    protected $table = 'vechicle';

    protected $primaryKey = 'id';

    protected $fillable = [
        'vehicle_number',
        'capacity',
        'driver_id',
    ];

    /**
     * Relationship: Vehicle belongs to Driver
     */
    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function routes()
    {
        return $this->hasMany(LogisticRoute::class, 'vehicle_id', 'id');
    }
}
