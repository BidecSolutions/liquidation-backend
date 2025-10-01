<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $countries = Country::with('regions')->latest()->get();
        return response()->json([
            'status' => true,
            'message' => 'Countries retrieved successfully.',
            'data' => $countries,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:countries,name',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $country = Country::create($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Country created successfully.',
            'data' => $country,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Country $country)
    {
        return response()->json([
            'status' => true,
            'message' => 'Country retrieved successfully.',
            'data' => $country->load('regions'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Country $country)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:countries,name,' . $country->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $country->update($validator->validated());

        return response()->json([
            'status' => true,
            'message' => 'Country updated successfully.',
            'data' => $country,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Country $country)
    {
        $country->delete();
        return response()->json([
            'status' => true,
            'message' => 'Country deleted successfully.'
        ]);
    }
}
