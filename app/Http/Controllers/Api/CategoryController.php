<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            // initialize so meta always has values
            $limit = null;
            $offset = null;


            $query = Category::with(['parent:id,name,slug,parent_id','listings' => function($q) {
                $q->withCount('views', 'watchers')->with('paymentMethod:id,name', 'shippingMethod:id,name', 'creator');
            }])->withCount('children as child_count', 'listings as listing_count');

            if ($request->filled('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            } else {
                $query->whereNull('parent_id');
            }

            if ($request->filled('category_type')) {
                $query->where('category_type', $request->category_type);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // clone for count before applying limit/offset
            $total = $query->count();

            // apply offset & limit only when limit is provided
            if ($request->filled('limit')) {
                $limit  = (int) $request->get('limit', 20);
                $offset = (int) $request->get('offset', 0);
                $query->skip($offset)->take($limit);
            }

            $categories = $query->orderBy('name', 'asc')->get();

            $categories->transform(function ($category){
                $category->child = $category->child_count > 0;
                return $category;
            });

            return response()->json([
                'status' => true,
                'message' => 'Categories fetched successfully',
                'data' => $categories,
                'meta' => [
                    'total'  => $total,
                    'limit'  => $limit,
                    'offset' => $offset
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }


    public function tree()
    {
        try {
            $categories = Category::with('childrenRecursive')
                ->whereNull('parent_id')
                ->orderBy('order')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Category tree fetched successfully',
                'data' => $categories
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }
    public function all()
    {
        try {
            $categories = Category::with('childrenRecursive')
                ->orderBy('order')
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'All Category fetched successfully',
                'data' => $categories
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function show($slug)
    {
        try {
            $category = Category::with('parent:id,name,slug,parent_id','listings')->where('slug', $slug)->first();

            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Category fetched',
                'data' => $category
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:categories',
                'description' => 'nullable|string',
                'category_type' => 'required|string|max:255',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'icon' => 'nullable|mimes:jpeg,png,jpg,gif,svg,webp|max:1024',
                'order' => 'nullable|integer',
                'status' => 'required|in:0,1',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'schema' => 'nullable|json',
                'canonical_url' => 'nullable|url',
                'focus_keywords' => 'nullable|string',
                'redirect_301' => 'nullable|url',
                'redirect_302' => 'nullable|url',
                'image_path_alt_name' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('image')) {
                $path = 'categories/images';
                if (!Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->makeDirectory($path, 0775, true);
                }
                $data['image_path'] = $request->file('image')->store($path, 'public');
                $data['image_path_name'] = $request->file('image')->getClientOriginalName();
            }

            if ($request->hasFile('icon')) {
                $path = 'categories/icons';
                if (!Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->makeDirectory($path, 0775, true);
                }
                $data['icon'] = $request->file('icon')->store($path, 'public');
            }

            $data['schema'] = $request->input('schema'); // Store as JSON string
            $data['created_by'] = auth('admin-api')->id();

            $category = Category::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Category created successfully',
                'data' => $category
            ], 201);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }


    public function update(Request $request, $id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                    'data' => null
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:categories,slug,' . $id,
                'description' => 'nullable|string',
                'category_type' => 'sometimes|required|string|max:255',
                'parent_id' => 'nullable|exists:categories,id',
                'image' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'icon' => 'nullable|mimes:jpeg,png,jpg,gif,svg|max:1024',
                'order' => 'nullable|integer',
                'status' => 'required|in:0,1',
                'meta_title' => 'nullable|string|max:255',
                'meta_description' => 'nullable|string',
                'schema' => 'nullable|json',
                'canonical_url' => 'nullable|url',
                'focus_keywords' => 'nullable|string',
                'redirect_301' => 'nullable|url',
                'redirect_302' => 'nullable|url',
                'image_path_alt_name' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'data' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Replace old image
            if ($request->hasFile('image')) {
                if ($category->image_path) {
                    Storage::disk('public')->delete($category->image_path);
                }
                $path = 'categories/images';
                Storage::disk('public')->makeDirectory($path, 0775, true, true);
                $data['image_path'] = $request->file('image')->store($path, 'public');
                $data['image_path_name'] = $request->file('image')->getClientOriginalName();
            }

            // Replace old icon
            if ($request->hasFile('icon')) {
                if ($category->icon) {
                    Storage::disk('public')->delete($category->icon);
                }
                $path = 'categories/icons';
                Storage::disk('public')->makeDirectory($path, 0775, true, true);
                $data['icon'] = $request->file('icon')->store($path, 'public');
            }

            // Keep raw schema string
            $data['schema'] = $request->input('schema');

            $category->update($data);

            return response()->json([
                'status' => true,
                'message' => 'Category updated successfully',
                'data' => $category
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }


    public function toggleStatus($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                    'data' => null
                ], 404);
            }

            $category->status = !$category->status;
            $category->save();

            return response()->json([
                'status' => true,
                'message' => 'Category status updated',
                'data' => [
                    'id' => $category->id,
                    'status' => $category->status
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $category = Category::find($id);

            if (!$category) {
                return response()->json([
                    'status' => false,
                    'message' => 'Category not found',
                    'data' => null
                ], 404);
            }

            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }

            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }

            $category->delete();

            return response()->json([
                'status' => true,
                'message' => 'Category deleted successfully',
                'data' => null
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'data' => $e->getMessage()
            ], 500);
        }
    }
}
