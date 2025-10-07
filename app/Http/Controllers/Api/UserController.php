<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserFeedback;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        try {
            $users = User::all(); //removed mapping needed all data of users

            return response()->json([
                'success' => true,
                'message' => "Successfully Fetched",
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Something went wrong: " . $e->getMessage(),
            ], 500);
        }
    }


    public function show($id)
    {
        try {
            $user = User::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'message' => "User fetched successfully",
                'data' => $user,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => "User not found",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Something went wrong: " . $e->getMessage(),
            ], 500);
        }
    }
    public function userSummary($userId)
    {
        $user = User::with(['listings', 'watchlist', 'feedbacks'])->find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => "User not found",
            ], 404);
        }

        // Listings stats
        $totalListings = $user->listings()->count();
        $activeListings = $user->listings()->where('status', 1)->count();
        $soldListings = $user->listings()->whereNotNull('sold_at')->count();
        $totalViews = $user->listings()->withCount('views')->get()->sum('views_count');

        // Watchlist
        $watchlistCount = $user->watchlist()->count();

        // Feedback summary
        $feedbacks = UserFeedback::where('reviewed_user_id', $user->id)->get();
        $totalFeedback = $feedbacks->count();
        $positive = $feedbacks->whereIn('rating', [4, 5])->count();
        $neutral  = $feedbacks->where('rating', 3)->count();
        $negative = $feedbacks->whereIn('rating', [1, 2])->count();

        return response()->json([
            'status' => true,
            'data' => [
                'personal' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'profile_photo' => $user->profile_photo,
                    'about_me' => $user->about_me,
                    'member_number' => $user->memberId,
                    'member_since' => $user->created_at->format('Y-m-d'),
                    'last_login' => $user->last_login_at,
                ],
                'listings' => [
                    'total' => $totalListings,
                    'active' => $activeListings,
                    'sold' => $soldListings,
                    'view_count' => $totalViews,
                ],
                'watchlist' => [
                    'count' => $watchlistCount,
                ],
                'feedback' => [
                    'total' => $totalFeedback,
                    'positive' => $positive,
                    'neutral' => $neutral,
                    'negative' => $negative,
                    'recent' => $feedbacks->take(5)->map(function ($fb) {
                        return [
                            'rating' => $fb->rating,
                            'review' => $fb->feedback_text,
                            'type'   => $fb->feedback_type,
                            'date'   => $fb->created_at->format('Y-m-d'),
                            'reviewer' => [
                                'id' => $fb->reviewer->id,
                                'name' => $fb->reviewer->name,
                                'username' => $fb->reviewer->username,
                            ],
                        ];
                    }),
                ],
                'recent_activity' => [
                    'last_search' => $user->searches()->latest()->first()?->keyword ?? null,
                    'last_viewed_listing' => $user->listings()->with('views')->latest()->first()?->title ?? null,
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        try {
                $request->validate([
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'nullable|string|min:8',
                    'phone' => 'nullable|string|max:20',
                    'billing_address' => 'nullable|string|max:500',
                    'gender' => 'nullable|string|max:100',
                    'street_address' => 'nullable|string|max:100',
                    'apartment' => 'nullable|string|max:100',
                    'city' => 'nullable|string|max:100',
                    'state' => 'nullable|string|max:100',
                    'zip_code' => 'nullable|string|max:20',
                ]);

                $data = $request->only([
                    'name', 'email', 'phone', 'billing_address',
                    'gender', 'street_address', 'apartment', 'city', 'state', 'zip_code'
                ]);

                // Always assign default hashed password if not provided
                $data['password'] = bcrypt($request->input('password', '123456'));

                $user = DB::transaction(function () use ($data) {
                    // Get latest customer_number and extract numeric part
                    $lastUser = User::orderByDesc('id')->first();
                    $lastNumber = 1000;

                    if ($lastUser && preg_match('/CN-(\d+)/', $lastUser->customer_number, $matches)) {
                        $lastNumber = (int)$matches[1];
                    }

                    $nextNumber = $lastNumber + 1;
                    $customerNumber = 'CN-' . $nextNumber;

                    // Check uniqueness in case of concurrent inserts
                    while (User::where('customer_number', $customerNumber)->exists()) {
                        $nextNumber++;
                        $customerNumber = 'CN-' . $nextNumber;
                    }

                    $data['customer_number'] = $customerNumber;
                    $data['created_by'] = auth('api')->id();

                    return User::create($data);
                });


                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => $user,
                ], 201);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Something went wrong: ' . $e->getMessage(),
                ], 500);
            }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,' . $id,
                'phone' => 'nullable|string|max:20',
                'billing_address' => 'nullable|string|max:500',
                'gender' => 'nullable|string|max:100',
                'street_address' => 'nullable|string|max:100',
                'apartment' => 'nullable|string|max:100',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
            ]);

            $data = $request->only([
                'name', 'email', 'phone', 'billing_address', 'gender',
                'street_address', 'apartment', 'city', 'state', 'zip_code'
            ]);

            // Always override with default password
            $data['password'] = bcrypt('123456');

            $user->update($data);

            return response()->json([
                'success' => true,
                'message' => 'User updated successfully with default password',
                'data' => $user->fresh(),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function changeactiveInactive($id)
    {
        try {
            $user = User::findOrFail($id);

            $user->status = $user->status == 1 ? 2 : 1; // Toggle status
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'User status updated successfully',
                'data' => [
                    'id' => $user->id,
                    'status' => $user->status == 1 ? 'active' : 'inactive',
                    'updated_at' => $user->updated_at->format('Y-m-d H:i:s'),
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong: ' . $e->getMessage(),
            ], 500);
        }
    }

}