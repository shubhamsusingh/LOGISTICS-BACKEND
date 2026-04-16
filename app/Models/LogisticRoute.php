<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogisticRoute extends Model
{
    // Table name (important because model name is different)
    protected $table = 'routes';

    // Primary key
    protected $primaryKey = 'route_id';

    // No timestamps in your table
    public $timestamps = false;

    // Mass assignable fields
    protected $fillable = [
        'vehicle_id',
        'route_date',
        'total_distance',
        'total_load',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // 🔗 Route belongs to Vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'id');
    }

    // 🔗 Route has many stops
    public function stops()
    {
        return $this->hasMany(RouteStop::class, 'route_id', 'route_id')
            ->orderBy('stop_order');
    }
}
