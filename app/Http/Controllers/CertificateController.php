<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class CertificateController extends Controller
{
    public function index()
    {
        $Cvs = Certificate::where('user_id', auth('api')->id())->get();

        return response()->json([
            'status' => true,
            'message' => 'All Cirtifciated Fetched successfull',
            'data' => $Cvs,
        ]);
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'certificate_name' => 'required|string|max:255',
            'issuer' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'no_expiry' => 'nullable|in:0,1',
            'document' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
        ]);
        if ($validated->fails()) {
            return response()->json(['success' => false, 'message' => $validated->errors()->first()], 422);
        }
        $data = $validated->validated();

        $path = null;
        if ($request->hasFile('document')) {
            $path = $request->file('document')->store('job_certificates', 'public');
        }

        $certificate = Certificate::create([
            'user_id' => auth('api')->id(),
            'certificate_name' => $data['certificate_name'],
            'issuer' => $data['issuer'] ?? null,
            'issue_date' => $data['issue_date'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'no_expiry' => $data['no_expiry'] ?? false,
            'document_path' => $path,
            'status' => $data['status'] ?? 1,
        ]);

        return response()->json(['success' => true, 'message' => 'Certificate added successfully.', 'data' => $certificate]);
    }

    public function update(Request $request, $id)
    {
        $certificate = Certificate::where('user_id', auth('api')->id())->findOrFail($id);

        $validated = Validator::make($request->all(), [
            'certificate_name' => 'sometimes|string|max:255',
            'issuer' => 'nullable|string|max:255',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:issue_date',
            'no_expiry' => 'boolean',
            'document' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
            'status' => 'nullable|in:0,1',
        ]);
        if ($validated->fails()) {
            return response()->json(['success' => false, 'message' => $validated->errors()->first()], 422);
        }

        if ($request->hasFile('document')) {
            if ($certificate->document_path) {
                Storage::disk('public')->delete($certificate->document_path);
            }
            $validated['document_path'] = $request->file('document')->store('job_certificates', 'public');
        }

        $certificate->update($validated);

        return response()->json(['success' => true, 'message' => 'Certificate updated successfully.', 'data' => $certificate]);
    }

    public function destroy($id)
    {
        $certificate = Certificate::where('user_id', auth('api')->id())->findOrFail($id);

        if ($certificate->document_path) {
            Storage::disk('public')->delete($certificate->document_path);
        }

        $certificate->delete();

        return response()->json(['success' => true, 'message' => 'Certificate deleted successfully.']);
    }
}
