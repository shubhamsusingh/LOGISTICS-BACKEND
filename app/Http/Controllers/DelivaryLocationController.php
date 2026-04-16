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
}
