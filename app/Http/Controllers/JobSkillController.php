<?php

namespace App\Http\Controllers;

use App\Models\JobProfile;
use App\Models\User;
use Illuminate\Http\Request;

class JobSkillController extends Controller
{
    public function index()
    {
        $userId = auth('api')->id();
        $user = User::where('id', $userId)->first();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'Job profile not found.',
            ], 404);
        }
        $skills = $user->skills()->where('status', 1)->get();

        return response()->json([
            'status' => true,
            'data' => $skills,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'skills' => 'required|array|max:30',
            'skills.*' => 'string|max:255',
        ]);

        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Please create a job profile first.',
            ], 400);
        }

        // Clear existing skills and reinsert
        $user->skills()->delete();
        foreach ($validated['skills'] as $skillName) {
            $user->skills()->create(['name' => $skillName, 'status' => 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Skills updated successfully.',
            'data' => $user->skills()->pluck('name'),
        ]);
    }

    public function destroy($id)
    {
        $user = auth('api')->user();
        $profile = $user->jobProfile;

        if (! $profile) {
            return response()->json([
                'success' => false,
                'message' => 'Please create a job profile first.',
            ], 400);
        }

        $skill = $profile->skills()->where('id', $id)->first();

        if (! $skill) {
            return response()->json([
                'success' => false,
                'message' => 'Skill not found.',
            ], 404);
        }

        $skill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Skill deleted successfully.',
        ]);
    }
}
