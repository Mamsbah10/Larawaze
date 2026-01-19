<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TrafficController;

// Test route
Route::get('/test', function () {
    return response()->json(['test' => 'ok']);
});

// Traffic / TomTom API routes
Route::prefix('traffic')->group(function () {
    // Get TomTom API key for frontend
    Route::get('/api-key', [TrafficController::class, 'getApiKey']);
    
    // Get traffic flow at a location
    Route::get('/flow', [TrafficController::class, 'getTrafficFlow']);
    
    // Get route with traffic
    Route::get('/route', [TrafficController::class, 'getRoute']);
    
    // Get traffic incidents
    Route::get('/incidents', [TrafficController::class, 'getIncidents']);
    
    // Proxy for TomTom traffic tiles (with CORS)
    Route::get('/tile/{z}/{x}/{y}', [TrafficController::class, 'getTrafficTile']);
});
