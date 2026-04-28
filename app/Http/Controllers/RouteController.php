<?php

namespace App\Http\Controllers;

use App\Models\DailyDemand;
use App\Models\DeliveryLocation;
use App\Models\LogisticRoute;
use App\Models\RouteStop;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    private float $depotLat = 21.98940;

    private float $depotLng = 72.86751;

    private float $lastRouteDistance = 0;

    public function generateRoutes()
    {
        DB::beginTransaction();

        try {
            $vehicles = Vehicle::all();

            if ($vehicles->isEmpty()) {
                return response()->json(['message' => 'No vehicles available'], 400);
            }

            $demands = DailyDemand::whereDate('demad_date', now())
                ->where('is_assigned', 0)
                ->orderByDesc('quantity')
                ->get();

            if ($demands->isEmpty()) {
                return response()->json(['message' => 'No unassigned demand found'], 400);
            }

            $routes = [];
            $currentRoute = [];
            $currentLoad = 0;
            $vehicleIndex = 0;

            // ✅ Use the actual vehicle's capacity for the current vehicle
            $currentVehicle = $vehicles[$vehicleIndex];
            $currentCapacity = $currentVehicle->capacity;

            foreach ($demands as $demand) {
                // ✅ If this demand alone exceeds any single vehicle capacity, skip or handle
                if ($demand->quantity > $currentCapacity) {
                    // Log or handle oversized demand — skip for now
                    continue;
                }

                if ($currentLoad + $demand->quantity <= $currentCapacity) {
                    // ✅ Fits in current vehicle — keep filling
                    $currentRoute[] = $demand;
                    $currentLoad += $demand->quantity;
                } else {
                    // ✅ Current vehicle is full — save route and move to next vehicle
                    if (! empty($currentRoute)) {
                        $routes[] = [
                            'vehicle' => $currentVehicle,
                            'demands' => $currentRoute,
                        ];
                    }

                    // Move to next vehicle (cycle if needed)
                    $vehicleIndex++;
                    if (! isset($vehicles[$vehicleIndex])) {
                        $vehicleIndex = 0;
                    }

                    $currentVehicle = $vehicles[$vehicleIndex];
                    $currentCapacity = $currentVehicle->capacity;

                    $currentRoute = [$demand];
                    $currentLoad = $demand->quantity;
                }
            }

            // ✅ Don't forget the last route
            if (! empty($currentRoute)) {
                $routes[] = [
                    'vehicle' => $currentVehicle,
                    'demands' => $currentRoute,
                ];
            }

            // ✅ Now create DB records
            foreach ($routes as $routeData) {
                $vehicle = $routeData['vehicle'];
                $demandsForRoute = $routeData['demands'];

                $totalLoad = collect($demandsForRoute)->sum('quantity');
                $orderedDemands = $this->optimizeRouteOrder($demandsForRoute);

                $route = LogisticRoute::create([
                    'vehicle_id' => $vehicle->id,
                    'route_date' => now(),
                    'total_distance' => $this->lastRouteDistance,
                    'total_load' => $totalLoad,
                ]);

                foreach ($orderedDemands as $index => $demand) {
                    RouteStop::create([
                        'route_id' => $route->route_id,
                        'location_id' => $demand->location_id,
                        'stop_order' => $index + 1,
                        'delivered_quantity' => 0, // ✅ start at 0
                    ]);

                    $demand->update(['is_assigned' => 1]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Routes generated successfully',
                'total_routes' => count($routes),
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ✅ THIS METHOD WAS MISSING — now restored
    private function optimizeRouteOrder(array $demands): array
    {
        $this->lastRouteDistance = 0;

        if (count($demands) === 0) {
            return $demands;
        }

        // Load all locations upfront
        $locationIds = collect($demands)->pluck('location_id')->unique()->toArray();
        $locations = DeliveryLocation::whereIn('id', $locationIds)->get()->keyBy('id');

        // Build stop list: each demand maps to its coordinates
        $stops = [];
        foreach ($demands as $demand) {
            $loc = $locations[$demand->location_id] ?? null;
            if (! $loc) {
                throw new \Exception("Location not found for location_id: {$demand->location_id}");
            }
            $stops[] = [
                'lat' => (float) $loc->latitude,
                'lng' => (float) $loc->longitude,
                'demand' => $demand,
            ];
        }

        $ordered = [];      // final ordered demand objects
        $totalDistance = 0.0;

        $currentLat = $this->depotLat;
        $currentLng = $this->depotLng;
        $remaining = $stops;   // unvisited stops

        while (! empty($remaining)) {
            $nearestIndex = null;
            $nearestDist = PHP_FLOAT_MAX;

            // Find the nearest unvisited stop from current position
            foreach ($remaining as $i => $stop) {
                $dist = $this->haversineDistance($currentLat, $currentLng, $stop['lat'], $stop['lng']);
                if ($dist < $nearestDist) {
                    $nearestDist = $dist;
                    $nearestIndex = $i;
                }
            }

            // Move to nearest stop
            $nearest = $remaining[$nearestIndex];
            $totalDistance += $nearestDist;

            $currentLat = $nearest['lat'];
            $currentLng = $nearest['lng'];

            $ordered[] = $nearest['demand'];

            // Remove visited stop
            array_splice($remaining, $nearestIndex, 1);
        }

        // Add return trip from last stop back to depot
        $totalDistance += $this->haversineDistance($currentLat, $currentLng, $this->depotLat, $this->depotLng);

        $this->lastRouteDistance = round($totalDistance / 1000, 2); // meters → km

        return $ordered;
    }

    private function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $lat1Rad = deg2rad($lat1);
        $lat2Rad = deg2rad($lat2);
        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLng = deg2rad($lng2 - $lng1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2)
            + cos($lat1Rad) * cos($lat2Rad)
            * sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function fetchDistanceMatrix(array $coords): array
    {
        $n = count($coords);
        $matrix = [];

        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($i === $j) {
                    $matrix[$i][$j] = 0;
                } else {
                    $matrix[$i][$j] = $this->haversineDistance(
                        $coords[$i]['lat'], $coords[$i]['lng'],
                        $coords[$j]['lat'], $coords[$j]['lng']
                    );
                }
            }
        }

        return $matrix;
    }
}
