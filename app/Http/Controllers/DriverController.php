<?php

namespace App\Http\Controllers;

use App\Models\Driver;

class DriverController extends Controller
{
    public function getAvailableDrivers()
    {
        $availableDrivers = Driver::with('user')
            ->where('status', 0)
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Available drivers fetched successfully',
            'data' => $availableDrivers,
        ]);
    }
}
