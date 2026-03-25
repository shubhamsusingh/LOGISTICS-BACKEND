<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function addVehicle(Request $request)
    {
        DB::beginTransaction();

        try {
            $driver = Driver::findOrFail($request->driver_id);
            $driver->status = 1;
            $driver->save();

            $vehicle = Vehicle::create([
                'vehicle_number' => $request->vehicle_number,
                'capacity' => $request->capacity,
                'driver_id' => $request->driver_id,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle added successfully',
                'data' => $vehicle,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function vehicleList()
    {
        $list = Vehicle::with('driver.user')->get();

        return response()->json([
            'status' => true,
            'data' => $list,
        ]);
    }
}
