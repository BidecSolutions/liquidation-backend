<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Enums\PromotionType;
use Illuminate\Support\Facades\Auth;

class PromotionController extends Controller
{
    /**
     * List promotions
     */
    public function index()
    {
        $promotions = Promotion::orderBy('priority', 'desc')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $promotions,
        ]);
    }

    /**
     * Store promotion
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|string|max:500',
            'type'  => 'nullable|string|in:' . implode(',', PromotionType::values()),
        ]);

        $promotion = Promotion::create([
            'title'        => $request->title,
            'subtitle'     => $request->subtitle,
            'description'  => $request->description,
            'image'        => $request->image,
            'redirect_url' => $request->redirect_url,
            'button_text'  => $request->button_text,
            'type'         => $request->type,
            'position'     => $request->position,
            'start_date'   => $request->start_date,
            'end_date'     => $request->end_date,
            'is_active'    => $request->boolean('is_active', false),
            'priority'     => $request->priority,
            'created_by'   => Auth::id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Promotion created successfully',
            'data'    => $promotion,
        ], 201);
    }

    /**
     * Show promotion
     */
    public function show($id)
    {
        $promotion = Promotion::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $promotion,
        ]);
    }

    /**
     * Update promotion
     */
    public function update(Request $request, $id)
    {
        $promotion = Promotion::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'image' => 'sometimes|required|string|max:500',
            'type'  => 'nullable|string|in:' . implode(',', PromotionType::values()),
        ]);

        $promotion->update($request->all());

        return response()->json([
            'status'  => true,
            'message' => 'Promotion updated successfully',
            'data'    => $promotion,
        ]);
    }

    /**
     * Delete promotion
     */
    public function destroy($id)
    {
        $promotion = Promotion::findOrFail($id);
        $promotion->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Promotion deleted successfully',
        ]);
    }
}
