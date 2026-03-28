<?php

namespace App\Http\Controllers;

use App\Models\DailyDemand;
use App\Models\DeliveryLocation;
// use App\Models\Route;
use App\Models\Vehicle;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function dashboardStats()
    {
        // 1. Total Vehicles
        $totalVehicles = Vehicle::count();

        // 2. Delivery Points
        $deliveryPoints = DeliveryLocation::count();

        // 3. Active Routes
        // $activeRoutes = Route::where('status', 1)->count();

        // 4. Today's Deliveries
        $today = Carbon::today();

        // $today = Carbon::today();

        $todayDemands = DailyDemand::with('location') // join location
            ->whereDate('demad_date', $today)
            ->get();
        $totalDeliveries = $todayDemands->count();

        $completedDeliveries = $todayDemands->where('status', 1)->count();
        $locationData = $todayDemands->map(function ($item) {
            return [
                'location_name' => $item->location->center_name ?? null,
                'latitude' => $item->location->latitude ?? null,
                'longitude' => $item->location->longitude ?? null,
                'quantity' => $item->quantity,
                'status' => $item->status,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'total_vehicles' => $totalVehicles,
                'delivery_points' => $deliveryPoints,
                // 'active_routes' => $activeRoutes,
                'today_deliveries' => [
                    'completed' => $completedDeliveries,
                    'total' => $totalDeliveries,
                ],
                'locationList' => $locationData,
                'today' => $today,
            ],
        ]);
    }
}
