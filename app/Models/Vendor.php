<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $table = 'vendors';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'vendor_name',
        'contact_persion',
        'phone',
        'latitude',
        'longitude',
    ];

    /**
     * Relationship: Vendor has many Delivery Locations
     */
    public function deliveryLocations()
    {
        return $this->hasMany(DeliveryLocation::class);
    }
}
