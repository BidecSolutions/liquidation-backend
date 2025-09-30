<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Enums\PromotionType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PromotionController extends Controller
{
    /**
     * List promotions
     */
    public function index()
    {
        $promotions = Promotion::select('id', 'title', 'description', 'image')->where('is_active', true)
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
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'type'  => 'nullable|string|in:' . implode(',', PromotionType::values()),
        ]);

        $data = $request->except('image');
        $data['created_by'] = Auth::id();
        $data['is_active'] = $request->boolean('is_active', false);

        if ($request->hasFile('image')) {
            $directory = 'promotions/images';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            $data['image'] = $request->file('image')->store($directory, 'public');
        }

        $promotion = Promotion::create($data);

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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'type'  => 'nullable|string|in:' . implode(',', PromotionType::values()),
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image if it exists
            if ($promotion->image) {
                Storage::disk('public')->delete($promotion->image);
            }
            $directory = 'promotions/images';
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }
            $data['image'] = $request->file('image')->store($directory, 'public');
        }

        $promotion->update($data);

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

        // Delete the image from storage
        if ($promotion->image) {
            Storage::disk('public')->delete($promotion->image);
        }
        $promotion->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Promotion deleted successfully',
        ]);
    }
}
