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

            $vehicleCapacity = $vehicles->first()->capacity;

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

            foreach ($demands as $demand) {
                if ($currentLoad + $demand->quantity <= $vehicleCapacity) {
                    $currentRoute[] = $demand;
                    $currentLoad += $demand->quantity;
                } else {
                    $routes[] = $currentRoute;
                    $currentRoute = [$demand];
                    $currentLoad = $demand->quantity;
                }
            }

            if (! empty($currentRoute)) {
                $routes[] = $currentRoute;
            }

            $vehicleIndex = 0;

            foreach ($routes as $routeData) {
                if (! isset($vehicles[$vehicleIndex])) {
                    $vehicleIndex = 0;
                }

                $vehicle = $vehicles[$vehicleIndex];
                $totalLoad = collect($routeData)->sum('quantity');

                // ✅ Optimize stop order using Haversine (no API needed)
                // ✅ Optimize stop order using Haversine (no API needed)
                $orderedDemands = $this->optimizeRouteOrder($routeData);

                $route = LogisticRoute::create([
                    'vehicle_id' => $vehicle->id,
                    'route_date' => now(),
                    'total_distance' => $this->lastRouteDistance, // ✅ now has real value in km
                    'total_load' => $totalLoad,
                ]);
                foreach ($orderedDemands as $index => $demand) {
                    RouteStop::create([
                        'route_id' => $route->route_id,
                        'location_id' => $demand->location_id,
                        'stop_order' => $index + 1,
                        'delivered_quantity' => $demand->quantity,
                    ]);

                    $demand->update(['is_assigned' => 1]);
                }

                $vehicleIndex++;
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
        // ✅ Always reset before each route calculation
        $this->lastRouteDistance = 0;

        if (count($demands) === 0) {
            return $demands;
        }

        // ✅ Handle single stop: depot → stop → back to depot
        if (count($demands) === 1) {
            $loc = DeliveryLocation::find($demands[0]->location_id);

            if ($loc) {
                $toStop = $this->haversineDistance($this->depotLat, $this->depotLng, $loc->latitude, $loc->longitude);
                $backToDepot = $this->haversineDistance($loc->latitude, $loc->longitude, $this->depotLat, $this->depotLng);
                $this->lastRouteDistance = round(($toStop + $backToDepot) / 1000, 2);
            }

            return $demands;
        }

        // Multiple stops
        $locationIds = collect($demands)->pluck('location_id')->unique()->toArray();
        $locations = DeliveryLocation::whereIn('id', $locationIds)->get()->keyBy('id');

        $coords = [[
            'lat' => $this->depotLat,
            'lng' => $this->depotLng,
            'demand' => null,
        ]];

        foreach ($demands as $demand) {
            $loc = $locations[$demand->location_id] ?? null;

            if (! $loc) {
                throw new \Exception("Location not found for location_id: {$demand->location_id}");
            }

            $coords[] = [
                'lat' => $loc->latitude,
                'lng' => $loc->longitude,
                'demand' => $demand,
            ];
        }

        $matrix = $this->fetchDistanceMatrix($coords);
        $n = count($coords);
        $visited = array_fill(0, $n, false);
        $visited[0] = true;
        $ordered = [];
        $orderedCoords = [];
        $current = 0;

        for ($step = 0; $step < $n - 1; $step++) {
            $nearest = null;
            $nearestDist = PHP_INT_MAX;

            for ($j = 1; $j < $n; $j++) {
                if (! $visited[$j] && $matrix[$current][$j] < $nearestDist) {
                    $nearest = $j;
                    $nearestDist = $matrix[$current][$j];
                }
            }

            $visited[$nearest] = true;
            $ordered[] = $coords[$nearest]['demand'];
            $orderedCoords[] = $nearest;
            $current = $nearest;
        }

        // Calculate total distance: depot → stop1 → stop2 → ... → last stop → depot
        $totalDistance = 0;
        $prev = 0;

        foreach ($orderedCoords as $idx) {
            $totalDistance += $matrix[$prev][$idx];
            $prev = $idx;
        }

        // ✅ Add return trip: last stop → depot
        $totalDistance += $matrix[$prev][0];

        $this->lastRouteDistance = round($totalDistance / 1000, 2); // km

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
