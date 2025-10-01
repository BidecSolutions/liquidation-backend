<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = City::with('governorate')->latest();

        if ($request->has('governorate_id')) {
            $query->where('governorate_id', $request->governorate_id);
        }

        $cities = $query->get();

        return response()->json([
            'status' => true,
            'message' => 'Cities retrieved successfully.',
            'data' => $cities,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'governorate_id' => 'required|exists:governorates,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $city = City::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'City created successfully.',
            'data' => $city->load('governorate'),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(City $city)
    {
        return response()->json([
            'status' => true,
            'message' => 'City retrieved successfully.',
            'data' => $city->load('governorate'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, City $city)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'governorate_id' => 'required|exists:governorates,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $city->update($validator->validated());

        return response()->json(['status' => true, 'message' => 'City updated successfully.', 'data' => $city->load('governorate')]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(City $city)
    {
        $city->delete();
        return response()->json(['status' => true, 'message' => 'City deleted successfully.']);
    }
}