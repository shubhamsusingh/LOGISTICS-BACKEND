<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    protected $table = 'route_stops';

    protected $primaryKey = 'stop_id';

    public $timestamps = false;

    protected $fillable = [
        'route_id',
        'location_id',
        'stop_order',
        'delivered_quantity',
    ];

    // 🔗 Relationship: Stop belongs to Route
    public function location()
    {
        return $this->belongsTo(DeliveryLocation::class, 'location_id', 'id');
    }

    // Also fix the route relationship (your model says Route::class but correct class is LogisticRoute):
    public function route()
    {
        return $this->belongsTo(LogisticRoute::class, 'route_id', 'route_id');
    }
}
