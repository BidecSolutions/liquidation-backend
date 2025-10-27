<?php

namespace App\Http\Controllers;

use App\Models\JobExperience;
use Illuminate\Http\Request;

class JobExperienceController extends Controller
{
    public function index()
    {
        $experiences = JobExperience::where('user_id', auth('api')->id())->get();

        // return response()->json($experiences);
        return response()->json([
            'status' => true,
            'message' => 'All Experienced.',
            'data' => $experiences,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'job_title' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'country' => 'nullable|string|max:255',
            'currently_working' => 'nullable|in:0,1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'nullable|in:0,1',
        ]);

        $experience = JobExperience::create(array_merge(
            $validated,
            ['user_id' => auth('api')->id(), 'status' => $request->status ?? 1]
        ));

        return response()->json([
            'status' => true,
            'message' => 'Experience added successfully.',
            'data' => $experience,
        ]);
    }

    public function update(Request $request, $id)
    {
        $experience = JobExperience::where('user_id', auth('api')->id())->findOrFail($id);

        $validated = $request->validate([
            'job_title' => 'sometimes|string|max:255',
            'company' => 'sometimes|string|max:255',
            'country' => 'nullable|string|max:255',
            'currently_working' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|string',
            'status' => 'nullable|in:0,1',
        ]);

        $experience->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Experience updated successfully.',
            'data' => $experience,
        ]);
    }

    public function destroy($id)
    {
        $experience = JobExperience::where('user_id', auth('api')->id())->findOrFail($id);
        $experience->delete();

        return response()->json(['success' => true, 'message' => 'Experience deleted.']);
    }
}
