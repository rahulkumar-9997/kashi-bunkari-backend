<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Helpers\ImageHelper;
class CustomerController extends Controller
{
   
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'Customer profile data fetched successfully.',
            'data' => $request->user()
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'unique:customers,email,' . $user->id
            ],
            'phone_number' => [
                'nullable',
                'digits:10',
                'unique:customers,phone_number,' . $user->id
            ],
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'bio' => 'nullable|string|max:500',
            'password' => 'nullable|min:6|confirmed',
            'profile_img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error',
                'errors' => $validator->errors()
            ], 422);
        }
        if ($request->phone_number) {
            $clean = preg_replace('/\D/', '', $request->phone_number);
            $request->merge([
                'phone_number' => substr($clean, -10)
            ]);
        }
        $data = $request->only([
            'name',
            'email',
            'phone_number',
            'gender',
            'date_of_birth',
            'bio'
        ]);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
            // optional logout all
            // $user->tokens()->delete();
        }
        if ($request->hasFile('profile_img')) {
            if ($user->profile_img) {
                ImageHelper::deleteSingleImage(
                    $user->profile_img,
                    'customer-profile'
                );
            }
            $timestamp = round(microtime(true) * 1000);
            $baseName = ImageHelper::generateFileName($user->name.'-profile-' . $user->id . '-' . $timestamp);
            $data['profile_img'] = ImageHelper::uploadSingleImageWebpOnly(
                $request->file('profile_img'),
                $baseName,
                'customer-profile'
            );
            Log::info('Profile image updated: ' . $data['profile_img']);
        }
        $user->update($data);
        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    
    public function logout(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
    
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices'
        ]);
    }
}