<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instruction;
use App\Enums\InstructionModule;
use Illuminate\Support\Facades\Auth;

class InstructionController extends Controller
{
    /**
     * List all instructions
     */
    public function index()
    {
        $instructions = Instruction::orderBy('position', 'asc')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data'   => $instructions,
        ]);
    }

    /**
     * Store instruction
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'image'       => 'required|string|max:500',
            'module'      => 'nullable|string|in:' . implode(',', InstructionModule::values()),
        ]);

        $instruction = Instruction::create([
            'title'       => $request->title,
            'description' => $request->description,
            'image'       => $request->image,
            'module'      => $request->module,
            'position'    => $request->position,
            'is_active'   => $request->boolean('is_active', true),
            'created_by'  => Auth::id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Instruction created successfully',
            'data'    => $instruction,
        ], 201);
    }

    /**
     * Show instruction
     */
    public function show($id)
    {
        $instruction = Instruction::findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => $instruction,
        ]);
    }

    /**
     * Update instruction
     */
    public function update(Request $request, $id)
    {
        $instruction = Instruction::findOrFail($id);

        $request->validate([
            'title'       => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'image'       => 'sometimes|required|string|max:500',
            'module'      => 'nullable|string|in:' . implode(',', InstructionModule::values()),
        ]);

        $instruction->update($request->all());

        return response()->json([
            'status'  => true,
            'message' => 'Instruction updated successfully',
            'data'    => $instruction,
        ]);
    }

    /**
     * Delete instruction
     */
    public function destroy($id)
    {
        $instruction = Instruction::findOrFail($id);
        $instruction->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Instruction deleted successfully',
        ]);
    }
}
