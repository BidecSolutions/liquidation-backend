<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Str;



class UserAuthController extends Controller
{
    // Register new user
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string',
                'username' => 'nullable|string|unique:users,username',
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6',
                'city' => 'nullable|string|max:20',
                'state' => 'nullable|string|max:20',
                'country' => 'nullable|string|max:20',
                'phone' => 'nullable|string|max:20',
                'gender' => 'nullable|string',
                'date_of_birth' => 'nullable|date',
                'billing_address' => 'nullable|string|max:500',
                'customer_number' => 'nullable|string|max:50',
            ]);
            $memberId = $this->generateMemberId();
            //member_number auto generate a random numeric number for memeber identification
            $customerNumber = 'CN' . strtoupper(uniqid());
            $user_code = $this->generateUniqueCode();
            // Ensure user_code is unique
            while (User::where('user_code', $user_code)->exists()) {
                $user_code = $this->generateUniqueCode();
            }
            $user = User::create([
                'name' => $request->name,
                'user_code' => $user_code,
                'username' => $request->username,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'billing_address' => $request->billing_address,
                'customer_number' => $customerNumber,
            ]);

            $token = $user->createToken('user-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => "Successfully Registered",
                'data' => $user,
                'token' => $token
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // Update user info
    // public function update(Request $request, $id)
    // {
    //     try {
    //         $user = User::findOrFail($id);

    //         $request->validate([
    //             'name' => 'sometimes|required|string',
    //             'email' => 'sometimes|required|email|unique:users,email,' . $id,
    //             'phone' => 'nullable|string|max:20',
    //             'billing_address' => 'nullable|string|max:500',
    //         ]);

    //         $user->update([
    //             'name' => $request->name ?? $user->name,
    //             'email' => $request->email ?? $user->email,
    //             'phone' => $request->phone ?? $user->phone,
    //             'billing_address' => $request->billing_address ?? $user->billing_address,
    //         ]);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'User updated successfully',
    //             'data' => $user,
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'User update failed',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }
    //Edit contact details
    //username check
    public function checkUsername(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
        ]);

        $username = $request->username;
        $exists   = \App\Models\User::where('username', $username)->exists();

        if ($exists) {
            $suggestions = [];
            $attempts = 0;

            // Generate up to 5 unique suggestions
            while (count($suggestions) < 5 && $attempts < 20) {
                $attempts++;

                // Append random number or letters to make it unique
                $newUsername = $username . rand(100, 999);

                // Optional: add random letters too
                if (rand(0, 1)) {
                    $newUsername .= chr(rand(97, 122)); // random lowercase letter
                }

                // Ensure it's not taken
                if (!\App\Models\User::where('username', $newUsername)->exists()) {
                    $suggestions[] = $newUsername;
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Username is already taken',
                'suggestions' => $suggestions,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Username is available',
        ], 200);
    }


    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'first_name' => 'nullable|string|max:100',
                'last_name' => 'nullable|string|max:100',
                'name' => 'nullable|string|max:255',
                'email' => 'nullable|email|unique:users,email,' . $user->id,
                'phone' => 'nullable|string|max:20',
                'landline' => 'nullable|string|max:20',
                'gender' => 'nullable|in:male,female,other',
                'account_type' => 'nullable|in:business,personal',
                'business_name' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:100',
                'address_finder' => 'nullable|string|max:255',
                'address_1' => 'nullable|string|max:255',
                'address_2' => 'nullable|string|max:255',
                'suburb' => 'nullable|string|max:255',
                'post_code' => 'nullable|string|max:20',
                'closest_district' => 'nullable|string|max:255',
                'billing_address' => 'nullable|string|max:500',
                'street_address' => 'nullable|string|max:255',
                'apartment' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:100',
                'state' => 'nullable|string|max:100',
                'zip_code' => 'nullable|string|max:20',
            ]);

            $user->update($request->only([
                'first_name',
                'last_name',
                'name',
                'email',
                'phone',
                'landline',
                'gender',
                'account_type',
                'business_name',
                'country',
                'address_finder',
                'address_1',
                'address_2',
                'suburb',
                'post_code',
                'closest_district',
                'billing_address',
                'street_address',
                'apartment',
                'city',
                'state',
                'zip_code',
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user->fresh(), // returns updated user
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


//update profile method to add occupation, about_me, and favourite quote

    public function updateProfileDetails(Request $request)
    {
        try {
            $user = $request->user();
            $request->validate([
                'occupation' => 'nullable|string|max:255',
                'about_me' => 'nullable|string|max:500',
                'favourite_quote' => 'nullable|string|max:500',
            ]);
            $user->update([
                'occupation' => $request->occupation,
                'about_me' => $request->about_me,
                'favourite_quote' => $request->favourite_quote,
            ]);
            return response()->json([
                'success' => true,
                'message' => 'Profile details updated successfully',
                'data' => $user->fresh(), // returns updated user
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    //change user name
    public function updateName(Request $request)
    {
        try {
            $request->validate([
                'new_name' => 'required|string|max:255',
                'password' => 'required',
            ]);

            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated user',
                ], 401);
            }

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password',
                ], 401);
            }

            $user->name = $request->new_name;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Name updated successfully',
                'data' => [
                    'name' => $user->name,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update name',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    //Change email

    public function updateEmail(Request $request)
    {
        try {
            $request->validate([
                'new_email' => 'required|email|unique:users,email',
                'password' => 'required',
            ]);

            if (!Hash::check($request->password, $request->user()->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect password',
                ], 401);
            }

            $user = $request->user();
            $user->email = $request->new_email;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Email updated successfully',
                'data' => [
                    'email' => $user->email,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(), // return full array of validation errors
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update email',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    //Change password

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);
        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 401);
        }
        $user->password = Hash::make($request->new_password);
        $user->save();
        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ], 200);
    }

    //forgot password (send reset link)
    public function sendResetLinkEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(),
            ], 422);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => true,
                'message' => 'Password reset link sent to your email address.'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => __($status),
        ], 400);
    }

    //reset password


    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'token' => 'required',
                'password' => 'required|string|min:8|confirmed',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'errors' => $e->errors(), // shows: "password must be at least 8 characters"
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status' => true,
                'message' => 'Password reset successful.'
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => __($status),
        ], 400);
    }
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $fieldtype = filter_var($request->email, FILTER_VALIDATE_EMAIL) ? 'email' : 'username'; 

        $user = User::where($fieldtype, $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 400);
        }

        $token = $user->createToken('user-token')->plainTextToken;
        
        return response()->json([
            'success' => true,
            'message' => 'Login Successfully',
            'data' => $user,
            'token' => $token,
        ], 200);
    }

    // User profile
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => "Successfully Fetched",
            'data' => $request->user(),
        ], 200);
    }

    // Upload profile photo
    public function uploadProfilePhoto(Request $request)
    {
        try {
             $user = $request->user();

            $request->validate([
                'profile_photo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            $path = $request->file('profile_photo')->store('uploads/profile_photos', 'public');
            $user->profile_photo = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile photo uploaded successfully',
                'data' => [
                    'profile_photo' => $path,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Something went wrong: ' . $e->getMessage()
                ],
                500
            );
        }
    }

    // Upload background photo
    public function uploadBackgroundPhoto(Request $request)
    {
        try {
            $user = $request->user();

            $request->validate([
                'background_photo' => 'required|image|mimes:jpeg,png,jpg|max:4096',
            ]);

            $path = $request->file('background_photo')->store('uploads/background_photos', 'public');
            $user->background_photo = $path;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Background photo uploaded successfully',
                'data' => [
                    'background_photo' => $path,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'User not found'
                ],
                404
            );
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Something went wrong: ' . $e->getMessage()
                ],
                500
            );
        }
    }
    // Logout user
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Successfully Logged out'
        ], 200);
    }

    private function generateUniqueCode()
    {
        // Characters: digits + uppercase letters
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        // Generate 8 random characters
        $randomString = '';
        for ($i = 0; $i < 8; $i++) {
            $randomString .= $characters[random_int(0, strlen($characters) - 1)];
        }

        // Add dash in the middle (after 4 chars)
        return substr($randomString, 0, 4) . substr($randomString, 4, 4);
    }
}
