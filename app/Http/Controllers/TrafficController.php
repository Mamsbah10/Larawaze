<?php

namespace App\Http\Controllers;

use App\Services\TomTomService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrafficController extends Controller
{
    protected TomTomService $tomTomService;

    public function __construct(TomTomService $tomTomService)
    {
        $this->tomTomService = $tomTomService;
    }

    /**
     * Get API key for frontend traffic visualization
     */
    public function getApiKey(): JsonResponse
    {
        return response()->json([
            'api_key' => $this->tomTomService->getApiKey(),
            'success' => true
        ]);
    }

    /**
     * Get traffic flow at specific location
     */
    public function getTrafficFlow(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $traffic = $this->tomTomService->getTrafficFlow(
            $validated['latitude'],
            $validated['longitude']
        );

        return response()->json($traffic);
    }

    /**
     * Get route with traffic information
     */
    public function getRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_lat' => 'required|numeric|between:-90,90',
            'start_lon' => 'required|numeric|between:-180,180',
            'end_lat' => 'required|numeric|between:-90,90',
            'end_lon' => 'required|numeric|between:-180,180',
        ]);

        $route = $this->tomTomService->getRouteWithTraffic(
            $validated['start_lat'],
            $validated['start_lon'],
            $validated['end_lat'],
            $validated['end_lon']
        );

        return response()->json($route);
    }

    /**
     * Get traffic incidents at a location
     */
    public function getIncidents(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $incidents = $this->tomTomService->getIncidents(
            $validated['latitude'],
            $validated['longitude']
        );

        return response()->json($incidents);
    }

    /**
     * Proxy TomTom traffic tiles with CORS headers
     * 
     * Note: Les erreurs 404 peuvent indiquer que:
     * 1. Les coordonnées de tuile (z/x/y) sont invalides pour cette région
     * 2. Cette région ne dispose pas de données traffic
     * 3. Le niveau de zoom n'est pas supporté
     * 
     * @param int $z Zoom level (0-28)
     * @param int $x Tile X coordinate
     * @param int $y Tile Y coordinate
     * @return \Illuminate\Http\Response PNG image or error
     */
    public function getTrafficTile($z, $x, $y): \Illuminate\Http\Response
    {
        try {
            // Valider les paramètres
            if ($z < 0 || $z > 28 || $x < 0 || $y < 0) {
                Log::warning("Invalid tile coordinates", [
                    'z' => $z, 'x' => $x, 'y' => $y
                ]);
                return response('Invalid tile coordinates', 400)
                    ->header('Access-Control-Allow-Origin', '*');
            }

            $baseUrl = $this->tomTomService->getBaseUrl();
            $apiKey = $this->tomTomService->getApiKey();
            $tileUrl = "{$baseUrl}/traffic/map/4/flow/absolute/{$z}/{$x}/{$y}.png?key={$apiKey}";
            
            // Log the request
            Log::debug("Traffic tile request", [
                'z' => $z,
                'x' => $x,
                'y' => $y,
                'url' => str_replace($apiKey, '***', $tileUrl)
            ]);
            
            $response = Http::withHeaders([
                'Referer' => 'http://127.0.0.1:8000',
                'User-Agent' => 'LaraWaze/1.0'
            ])->timeout(30)->get($tileUrl);

            if ($response->failed()) {
                Log::warning("TomTom tile request failed", [
                    'status' => $response->status(),
                    'z' => $z,
                    'x' => $x,
                    'y' => $y,
                    'url' => str_replace($apiKey, '***', $tileUrl),
                    'response_body' => substr($response->body(), 0, 200)
                ]);
                
                // Return 404 with more info
                return response(json_encode([
                    'error' => 'Tile not found',
                    'message' => 'This tile is not available from TomTom. Check coordinates or region coverage.',
                    'coordinates' => ['z' => $z, 'x' => $x, 'y' => $y],
                    'tip' => 'Try with valid coordinates like z=15, x=16408, y=10729 (Paris)'
                ]), 404)
                    ->header('Content-Type', 'application/json')
                    ->header('Access-Control-Allow-Origin', '*');
            }

            Log::debug("Traffic tile served successfully", [
                'z' => $z,
                'x' => $x,
                'y' => $y,
                'size' => strlen($response->body())
            ]);

            return response($response->body(), 200)
                ->header('Content-Type', 'image/png')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Cache-Control', 'public, max-age=3600');
        } catch (\Exception $e) {
            Log::error("Traffic tile proxy error", [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'z' => $z,
                'x' => $x,
                'y' => $y,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response(json_encode([
                'error' => 'Tile proxy error',
                'message' => 'An error occurred while fetching the tile from TomTom'
            ]), 500)
                ->header('Content-Type', 'application/json')
                ->header('Access-Control-Allow-Origin', '*');
        }
    }
}
