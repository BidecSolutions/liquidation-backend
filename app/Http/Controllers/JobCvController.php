<?php

namespace App\Http\Controllers;

use App\Models\JobCv;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class JobCvController extends Controller
{
    public function index()
    {
        return response()->json(
            JobCv::where('user_id', auth('api')->id())->get()
        );
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'cv_file' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'is_selected' => 'boolean',
            'status' => 'nullable|in:0,1',
        ]);
        if($validated->fails()){
            return response()->json(['success' => false, 'message' => $validated->errors()->first()], 422);
        }
        
        // dd('awdawd');

        $userId = auth('api')->id();
        $user = User::find($userId);
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found.'], 404);
        }
        // Check limit
        if ($user->jobCvs()->count() >= 3) {
            return response()->json(['success' => false, 'message' => 'You can upload a maximum of 3 CVs.'], 422);
        }

        // Upload
        $path = $request->file('cv_file')->store('job_cvs', 'public');

        // If selected, unselect others
        if ($request->boolean('is_selected')) {
            $user->jobCvs()->update(['is_selected' => false]);
        }

        $cv = JobCv::create([
            'user_id' => $user->id,
            'file_path' => $path,
            'is_selected' => $request->boolean('is_selected'),
            'status' => $request->status ?? 1,
        ]);

        return response()->json(['success' => true, 'message' => 'CV uploaded successfully.', 'data' => $cv]);
    }

    public function update(Request $request, $id)
    {
        $cv = JobCv::where('user_id', auth('api')->id())->findOrFail($id);

        $validated = $request->validate([
            'is_selected' => 'boolean',
            'status' => 'nullable|in:0,1',
        ]);

        if ($request->boolean('is_selected')) {
            JobCv::where('user_id', auth('api')->id())->update(['is_selected' => false]);
        }

        $cv->update($validated);

        return response()->json(['success' => true, 'message' => 'CV updated successfully.', 'data' => $cv]);
    }

    public function destroy($id)
    {
        $cv = JobCv::where('user_id', auth('api')->id())->findOrFail($id);

        if ($cv->file_path) {
            Storage::disk('public')->delete($cv->file_path);
        }

        $cv->delete();

        return response()->json(['success' => true, 'message' => 'CV deleted successfully.']);
    }
}
