<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FavoriteAddress;

class FavoriteAddressController extends Controller
{
    public function index(Request $request)
    {
        $uid = session()->get('user_id');
        if (!$uid) return response()->json([], 401);
        $list = FavoriteAddress::where('user_id', $uid)->orderBy('id', 'desc')->get();
        return response()->json($list);
    }

    public function store(Request $request)
    {
        $uid = session()->get('user_id');
        if (!$uid) return response()->json(['error' => 'Unauthorized'], 401);

        $data = $request->only(['name','type','latitude','longitude','address']);
        $data['user_id'] = $uid;

        $fav = FavoriteAddress::create([
            'user_id' => $uid,
            'name' => $data['name'] ?? 'Adresse',
            'type' => $data['type'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        return response()->json($fav, 201);
    }

    public function destroy(Request $request, $id)
    {
        $uid = session()->get('user_id');
        if (!$uid) return response()->json(['error' => 'Unauthorized'], 401);

        $fav = FavoriteAddress::find($id);
        if (!$fav || $fav->user_id != $uid) return response()->json(['error' => 'Not found'], 404);

        $fav->delete();
        return response()->json(['deleted' => true]);
    }
}
