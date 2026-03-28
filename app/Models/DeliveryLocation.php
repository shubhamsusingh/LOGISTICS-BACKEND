<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryLocation extends Model
{
    use HasFactory;

    protected $table = 'delivery_locations';

    protected $primaryKey = 'id';

    public $timestamps = true; // because you have created_at and updated_at

    protected $fillable = [
        'vendor_id',
        'center_name',
        'address',
        'latitude',
        'longitude',
    ];

    // If you want to allow mass assignment for all fields (not recommended for security)
    // protected $guarded = [];

    /**
     * Relationship: DeliveryLocation belongs to Vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function dailyDemands()
    {
        return $this->hasMany(DailyDemand::class, 'location_id');
    }
}
