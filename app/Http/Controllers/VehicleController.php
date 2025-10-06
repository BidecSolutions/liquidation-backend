<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    //
    public function list(Request $request){
        $make = $request->input('make');
        $model = $request->input('model');
        $year = $request->input('year');
        $query = Vehicle::select('make', 'model', 'year');
        if($make){
            $query = $query->where('make', $make)->whereNotNull('model');
        }
        if($model){
            $query = $query->where('model', $model);
        }
        if($year){
            $query = $query->where('year', $model);
        }
        $VehicelMake =  $query->get();
        return response()->json([
            'status' => true,
            'message' => 'vehicle data successfully fetched',
            'data' => $VehicelMake,
        ]);
    }
}
