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
    public function route()
    {
        return $this->belongsTo(Route::class, 'route_id', 'route_id');
    }

    // 🔗 Relationship: Stop belongs to Delivery Location
    public function location()
    {
        return $this->belongsTo(DeliveryLocation::class, 'location_id', 'location_id');
    }
}
