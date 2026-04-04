<?php

namespace App\Http\Controllers;

use App\Models\DailyDemand;
use App\Models\DeliveryLocation;
use App\Models\Vehicle;
use App\Models\Vendor;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function dashboardStats()
    {
        // 1. Total Vehicles
        $totalVehicles = Vehicle::count();
        $vendor = Vendor::first();
        $vendorData = [
            'vendor_name' => $vendor->vendor_name ?? null,
            'start_latitude' => $vendor->latitude ?? null,
            'start_longitude' => $vendor->longitude ?? null,
        ];

        // 2. Delivery Points
        $deliveryLocations = DeliveryLocation::all();

        $deliveryLocationData = $deliveryLocations->map(function ($item) {
            return [
                'location_name' => $item->center_name ?? null,
                'latitude' => $item->latitude ?? null,
                'longitude' => $item->longitude ?? null,
            ];
        });

        $totalDeliveryPoints = $deliveryLocations->count();

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
        // $locationData = $todayDemands->map(function ($item) {
        //     return [
        //         'location_name' => $item->location->center_name ?? null,
        //         'latitude' => $item->location->latitude ?? null,
        //         'longitude' => $item->location->longitude ?? null,
        //         'quantity' => $item->quantity,
        //         'status' => $item->status,
        //     ];
        // });

        return response()->json([
            'status' => true,
            'data' => [
                'total_vehicles' => $totalVehicles,
                'delivery_points' => $totalDeliveryPoints,
                // 'active_routes' => $activeRoutes,
                'today_deliveries' => [
                    'completed' => $completedDeliveries,
                    'total' => $totalDeliveries,
                ],
                'locationList' => $deliveryLocationData,
                'vendor' => $vendorData,
                'today' => $today,
            ],
        ]);
    }
}
