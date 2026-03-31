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

    public function updateVehicle(Request $request)
    {
        DB::beginTransaction();

        try {
            // Find vehicle
            $vehicle = Vehicle::findOrFail($request->id);

            // Check duplicate vehicle_number (ignore current record)
            $exists = Vehicle::where('vehicle_number', $request->vehicle_number)
                ->where('id', '!=', $request->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Vehicle number already exists',
                ]);
            }

            // If driver is changed
            if ($vehicle->driver_id != $request->driver_id) {

                $oldDriver = Driver::find($vehicle->driver_id);
                if ($oldDriver) {
                    $oldDriver->status = 0;
                    $oldDriver->save();
                }

                $newDriver = Driver::findOrFail($request->driver_id);
                $newDriver->status = 1;
                $newDriver->save();
            }

            // Update vehicle
            $vehicle->update([
                'vehicle_number' => $request->vehicle_number,
                'capacity' => $request->capacity,
                'driver_id' => $request->driver_id,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle updated successfully',
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

    public function deleteVehicle($id)
    {
        DB::beginTransaction();

        try {
            // Find vehicle
            $vehicle = Vehicle::findOrFail($id);

            // Set driver inactive (release driver)
            $driver = Driver::find($vehicle->driver_id);
            if ($driver) {
                $driver->status = 0;
                $driver->save();
            }

            // Delete vehicle
            $vehicle->delete();

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Vehicle deleted successfully',
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
