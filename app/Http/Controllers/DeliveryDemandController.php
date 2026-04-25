<?php

namespace App\Http\Controllers;

use App\Models\DailyDemand;
use App\Models\DeliveryLocation;
use Illuminate\Http\Request;

class DeliveryDemandController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'location_id' => 'required|integer',
            'demad_date' => 'required|date',
            'quantity' => 'required|integer',
        ]);

        $location = DeliveryLocation::find($request->location_id);

        if (! $location) {
            return response()->json([
                'message' => 'Invalid location Demand',
            ], 404);
        }

        // 🔍 Check if demand already exists
        $existingDemand = DailyDemand::where('location_id', $request->location_id)
            ->whereDate('demad_date', $request->demad_date)
            ->first();

        if ($existingDemand) {
            // ✅ Update only quantity
            $existingDemand->update([
                'quantity' => $request->quantity,
            ]);

            return response()->json([
                'message' => 'Demand updated successfully',
                'data' => $existingDemand,
            ], 200);
        }

        // ✅ Create new demand
        $demand = DailyDemand::create([
            'location_id' => $request->location_id,
            'demad_date' => $request->demad_date,
            'quantity' => $request->quantity,
            'status' => 0,
            'is_assigned' => 0,
        ]);

        return response()->json([
            'message' => 'Demand added successfully',
            'data' => $demand,
        ], 201);
    }

    public function getDeliveryDemand()
    {
        $demad = DailyDemand::with('location')->whereDate('demad_date', now())->get();

        return response()->json([
            'message' => 'Daily Demand fetched successfully',
            'data' => $demad,
        ], 200);
    }
}
