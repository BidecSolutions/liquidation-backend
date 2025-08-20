<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Code;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Exception;

class CodeController extends Controller
{
    public function index()
    {
        try {
            $codes = Code::orderBy('sort_order')->get();
            return response()->json(['success' => true, 'data' => $codes]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'key' => 'required|string|max:255',
                'value' => 'nullable|string',
                'sort_order' => 'nullable|integer',
                'status' => 'boolean',
            ]);

            $code = Code::create($request->all());

            return response()->json(['success' => true, 'message' => 'Code created successfully', 'data' => $code]);
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $code = Code::findOrFail($id);
            return response()->json(['success' => true, 'data' => $code]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Code not found'], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'key' => 'sometimes|required|string|max:255',
                'value' => 'nullable|string',
                'sort_order' => 'nullable|integer',
                'status' => 'boolean',
            ]);

            $code = Code::findOrFail($id);
            $code->update($request->all());

            return response()->json(['success' => true, 'message' => 'Code updated successfully', 'data' => $code]);
        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $code = Code::findOrFail($id);
            $code->delete();
            return response()->json(['success' => true, 'message' => 'Code deleted successfully']);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Code not found'], 404);
        }
    }

    public function toggleStatus($id)
    {
        try {
            $code = Code::findOrFail($id);
            $code->status = !$code->status;
            $code->save();

            return response()->json(['success' => true, 'message' => 'Status toggled successfully', 'data' => $code]);
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => 'Code not found'], 404);
        }
    }
}
