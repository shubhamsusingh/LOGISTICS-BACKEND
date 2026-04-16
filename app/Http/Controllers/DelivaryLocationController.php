<?php

namespace App\Http\Controllers;

use App\Models\DeliveryLocation;
use App\Models\Vendor;
use Illuminate\Http\Request;

class DelivaryLocationController extends Controller
{
    public function store(Request $request)
    {
        // Step 1: Validate input
        $request->validate([
            'vendor_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'required|string',
        ]);

        // Step 2: Check if vendor exists
        $vendor = Vendor::find($request->vendor_id);

        if (! $vendor) {
            return response()->json([
                'message' => 'Vendor not found',
            ], 404);
        }

        // Step 3: Store delivery location
        $location = DeliveryLocation::create([
            'vendor_id' => $request->vendor_id,
            'center_name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
        ]);

        return response()->json([
            'message' => 'Delivery location added successfully',
            'data' => $location,
        ], 201);
    }

    public function update(Request $request)
    {
        // Step 1: Validate input
        $request->validate([
            'id' => 'required',
            'vendor_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'address' => 'required|string',
        ]);

        // Step 2: Find location
        $location = DeliveryLocation::find($request->id);

        if (! $location) {
            return response()->json([
                'message' => 'Delivery location not found',
            ], 404);
        }

        // Step 3: Check if vendor exists
        $vendor = Vendor::find($request->vendor_id);

        if (! $vendor) {
            return response()->json([
                'message' => 'Vendor not found',
            ], 404);
        }

        // Step 4: Update location
        $location->update([
            'vendor_id' => $request->vendor_id,
            'center_name' => $request->name,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'address' => $request->address,
        ]);

        return response()->json([
            'message' => 'Delivery location updated successfully',
            'data' => $location,
        ], 200);
    }

    public function destroy($id)
    {
        // Step 1: Find location
        $location = DeliveryLocation::find($id);

        if (! $location) {
            return response()->json([
                'message' => 'Delivery location not found',
            ], 404);
        }

        // Step 2: Delete
        $location->delete();

        return response()->json([
            'message' => 'Delivery location deleted successfully',
        ], 200);
    }

    public function getVendorLocations()
    {
        $locations = DeliveryLocation::with('vendor')
            ->where('vendor_id', 1)
            ->get();

        return response()->json([
            'message' => 'Delivery locations fetched successfully',
            'data' => $locations,
        ], 200);
    }
}
