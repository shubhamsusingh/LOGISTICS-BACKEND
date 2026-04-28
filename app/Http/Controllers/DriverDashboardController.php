<?php

namespace App\Http\Controllers;

use App\Models\DailyDemand;
use App\Models\Driver;
use App\Models\RouteStop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverDashboardController extends Controller
{
    /**
     * GET /api/driver/dashboard
     * Full dashboard: driver info, vehicle, all routes with stops & demand
     */
    public function getDashboard()
    {
        $driver = Driver::with('user')
            ->where('user_id', Auth::id())
            ->first();

        if (! $driver) {
            return response()->json([
                'success' => false,
                'message' => 'Driver profile not found.',
            ], 404);
        }

        // Get vehicle assigned to this driver
        $vehicle = $driver->vehicle;

        if (! $vehicle) {
            return response()->json([
                'success' => false,
                'message' => 'No vehicle assigned to this driver.',
            ], 404);
        }

        // Get all routes for this vehicle, with stops -> location -> dailyDemands
        $routes = $vehicle->routes()
            ->with([
                'stops' => function ($query) {
                    $query->orderBy('stop_order');
                },
                'stops.location',
                'stops.location.dailyDemands',
            ])
            ->whereDate('route_date', now()->toDateString())  // ✅ only today
            ->orderBy('route_date', 'desc')
            ->get();
        // Format routes
        $routesData = $routes->map(function ($route) {
            $stops = $this->formatStops($route->stops, $route->route_date);

            return [
                'route_id' => $route->route_id,
                'route_date' => $route->route_date,
                'total_distance' => $route->total_distance,
                'total_load' => $route->total_load,
                'total_stops' => $stops->count(),
                'delivered_stops' => $stops->where('delivery_status', 'delivered')->count(),
                'pending_stops' => $stops->where('delivery_status', 'undelivered')->count(),
                'stops' => $stops->values(),
            ];
        });

        // Overall summary
        $summary = [
            'total_routes' => $routesData->count(),
            'total_stops' => $routesData->sum('total_stops'),
            'delivered_stops' => $routesData->sum('delivered_stops'),
            'pending_stops' => $routesData->sum('pending_stops'),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'driver' => [
                    'driver_id' => $driver->id,
                    'name' => $driver->user->name ?? null,
                    'email' => $driver->user->email ?? null,
                    'status' => $driver->status == 1 ? 'assigned' : 'not assigned',
                ],
                'vehicle' => [
                    'vehicle_id' => $vehicle->id,
                    'vehicle_number' => $vehicle->vehicle_number,
                    'capacity' => $vehicle->capacity,
                ],
                'summary' => $summary,
                'routes' => $routesData,
            ],
        ]);
    }

    /**
     * GET /api/driver/route/{routeId}
     * Single route detail with ordered stops and demand
     */
    public function getRouteDetail($routeId)
    {
        $driver = Driver::where('user_id', Auth::id())->first();

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 404);
        }

        $vehicle = $driver->vehicle;

        if (! $vehicle) {
            return response()->json(['success' => false, 'message' => 'No vehicle assigned.'], 404);
        }

        // Load the route — must belong to this driver's vehicle
        $route = $vehicle->routes()
            ->with([
                'stops' => function ($query) {
                    $query->orderBy('stop_order');
                },
                'stops.location',
                'stops.location.dailyDemands',
            ])
            ->where('route_id', $routeId)
            ->first();

        if (! $route) {
            return response()->json([
                'success' => false,
                'message' => 'Route not found or not assigned to you.',
            ], 404);
        }

        $stops = $this->formatStops($route->stops, $route->route_date);

        return response()->json([
            'success' => true,
            'data' => [
                'route' => [
                    'route_id' => $route->route_id,
                    'route_date' => $route->route_date,
                    'total_distance' => $route->total_distance,
                    'total_load' => $route->total_load,
                    'total_stops' => $stops->count(),
                    'delivered_stops' => $stops->where('delivery_status', 'delivered')->count(),
                    'pending_stops' => $stops->where('delivery_status', 'undelivered')->count(),
                ],
                'stops' => $stops->values(),
            ],
        ]);
    }

    /**
     * POST /api/driver/stop/{stopId}/mark-delivered
     * Mark a stop as delivered and update daily_demand status
     * Body: { "delivered_quantity": 150 }
     */
    public function markDelivered(Request $request, $stopId)
    {
        $request->validate([
            'delivered_quantity' => 'required|integer|min:0',
        ]);

        $driver = Driver::where('user_id', Auth::id())->first();

        if (! $driver) {
            return response()->json(['success' => false, 'message' => 'Driver not found.'], 404);
        }

        $vehicle = $driver->vehicle;

        // Load the stop with its route — verify it belongs to this driver's vehicle
        $stop = RouteStop::with(['route', 'location'])
            ->where('stop_id', $stopId)
            ->whereHas('route', function ($query) use ($vehicle) {
                $query->where('vehicle_id', $vehicle->id);
            })
            ->first();

        if (! $stop) {
            return response()->json([
                'success' => false,
                'message' => 'Stop not found or does not belong to your route.',
            ], 404);
        }

        // Update delivered quantity on the stop
        $stop->delivered_quantity = $request->delivered_quantity;
        $stop->save();

        // Update matching daily_demand to delivered (status = 1)
        DailyDemand::where('location_id', $stop->location_id)
            ->where('demad_date', $stop->route->route_date)
            ->update([
                'status' => 1,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Delivery marked as completed successfully.',
            'data' => [
                'stop_id' => $stop->stop_id,
                'delivered_quantity' => $stop->delivered_quantity,
                'delivery_status' => 'delivered',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Private Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Format stop collection — attaches demand matched by route_date
     */
    private function formatStops($stops, $routeDate)
    {
        return $stops->map(function ($stop) use ($routeDate) {
            $location = $stop->location;

            // Match demand for this location on the specific route date
            $demand = $location
                ? $location->dailyDemands->firstWhere('demad_date', $routeDate)
                : null;

            return collect([
                'stop_id' => $stop->stop_id,
                'stop_order' => $stop->stop_order,
                'location_id' => $stop->location_id,
                'center_name' => $location->center_name ?? null,
                'address' => $location->address ?? null,
                'latitude' => $location->latitude ?? null,
                'longitude' => $location->longitude ?? null,
                'demand_quantity' => $demand->quantity ?? 0,
                'delivered_quantity' => $stop->delivered_quantity,
                'delivery_status' => ($demand && $demand->status == 1) ? 'delivered' : 'undelivered',
                'is_assigned' => ($demand && $demand->is_assigned == 1),
            ]);
        });
    }
}
