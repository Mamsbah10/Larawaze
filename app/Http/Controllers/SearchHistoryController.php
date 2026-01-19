<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SearchHistory;

class SearchHistoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) return response()->json([], 200);
        $items = SearchHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'lat' => 'required|numeric',
            'lon' => 'required|numeric',
        ]);

        // Create record (map to existing DB columns)
        $rec = SearchHistory::create([
            'user_id' => $userId,
            'query' => $data['name'],
            'address' => $data['name'],
            'latitude' => $data['lat'],
            'longitude' => $data['lon'],
        ]);

        // Keep only last 50 entries
        $count = SearchHistory::where('user_id', $userId)->count();
        if ($count > 50) {
            $tooOld = SearchHistory::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->skip(50)
                ->take($count - 50)
                ->pluck('id');
            SearchHistory::whereIn('id', $tooOld)->delete();
        }

        return response()->json($rec, 201);
    }

    public function clear(Request $request)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);
        SearchHistory::where('user_id', $userId)->delete();
        return response()->json([], 204);
    }

    public function destroy(Request $request, $id)
    {
        $userId = $request->session()->get('user_id');
        if (!$userId) return response()->json(['error' => 'Unauthorized'], 401);

        $rec = SearchHistory::where('id', $id)->where('user_id', $userId)->first();
        if (!$rec) return response()->json(['error' => 'Not Found'], 404);

        $rec->delete();
        return response()->json([], 204);
    }
}
