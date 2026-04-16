<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyDemand extends Model
{
    use HasFactory;

    protected $table = 'daily_demand';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'location_id',
        'demad_date',
        'quantity',
        'status',
        'is_assigned',
    ];

    /**
     * Relationship: DailyDemand belongs to DeliveryLocation
     */
    public function location()
    {
        return $this->belongsTo(DeliveryLocation::class, 'location_id');
    }
}
