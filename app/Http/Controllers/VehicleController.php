<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;

class VehicleController extends Controller
{
    public function vehicleList()
    {
        $list = Vehicle::with('driver.user')->get();

        return response()->json([
            'status' => true,
            'data' => $list,
        ]);
    }
}
