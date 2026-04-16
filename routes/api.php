<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;
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
});
