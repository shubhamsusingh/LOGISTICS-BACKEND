<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DelivaryLocationController;
use App\Http\Controllers\DeliveryDemandController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\DriverDashboardController;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::Post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/getDriverList', [DriverController::class, 'getAvailableDrivers']);
    Route::get('/vehicleList', [VehicleController::class, 'vehicleList']);
    Route::post('/addVehicle', [VehicleController::class, 'addVehicle']);
    Route::get('/dashboard', [AdminDashboardController::class, 'dashboardStats']);
    Route::post('updateVehicle', [VehicleController::class, 'updateVehicle']);
    Route::delete('/vehicle/{id}', [VehicleController::class, 'deleteVehicle']);
    Route::get('/generate-routes', [RouteController::class, 'generateRoutes']);
    Route::post('/add-locations', [DelivaryLocationController::class, 'store']);
    Route::get('/delivary-location-list', [DelivaryLocationController::class, 'getVendorLocations']);
    Route::post('/update', [DelivaryLocationController::class, 'update']);
    Route::delete('/delete-locations/{id}', [DelivaryLocationController::class, 'destroy']);
    Route::get('/Delivery-demad', [DeliveryDemandController::class, 'getDeliveryDemand']);
    Route::post('/add-demand', [DeliveryDemandController::class, 'store']);
});
Route::middleware('auth:sanctum')->prefix('driver')->group(function () {

    // Full dashboard — driver + vehicle + all routes with stops & demand
    Route::get('/driver-dashboard', [DriverDashboardController::class, 'getDashboard']);

    // Single route detail with ordered stops
    Route::get('/route/{routeId}', [DriverDashboardController::class, 'getRouteDetail']);

    // Mark a stop as delivered
    Route::post('/stop/{stopId}/mark-delivered', [DriverDashboardController::class, 'markDelivered']);

});
