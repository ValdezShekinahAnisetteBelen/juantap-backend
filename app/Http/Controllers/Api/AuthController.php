<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Register
    public function register(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
            'confirmPassword' => 'required|string|min:6|same:password',
        ]);

        $user = User::create([
        'firstname' => $request->firstname,
        'lastname'  => $request->lastname,
        'name'      => $request->firstname . ' ' . $request->lastname,
        'email'     => $request->email,
        'password'  => Hash::make($request->password),
        'profile_image' => 'defaults/avatar.png', // store default path
    ]);

        // ✅ Create empty profile for new user
        $user->profile()->create([
            'bio' => '',
            'phone' => '',
            'website' => '',
            'location' => '',
        ]);

        // ✅ Send email verification
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Registered successfully. A welcome email has been sent!',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => (bool)$user->is_admin,
            ],
        ], 201);
    }

    public function getUserById($id)
{
    $user = User::with('profile.socialLinks')->find($id);

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    return response()->json([
    'id' => $user->id,
    'name' => $user->name,
    'username' => $user->username ?? null, // ✅ include username for the frontend
    'email' => $user->email,
    'is_admin' => (bool)$user->is_admin,
    'avatar_url' => $user->profile_image
        ? asset($user->profile_image)
        : asset('avatars/default.png'),
    'profile' => $user->profile,
]);

}


    // Login
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $user = Auth::user();

        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Account not verified'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
        'access_token' => $token,
        'token_type' => 'Bearer',
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool)$user->is_admin,
    ],
        ]);
    }

public function index()
{
    $users = User::where('is_admin', 0)->get()->map(function ($user) {
        return [
            'id' => $user->id,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'avatar_url' => $user->profile_image
                ? asset($user->profile_image)
                : asset('avatars/default.png'),
        ];
    });

    return response()->json(['users' => $users]);
}


    public function user(Request $request)
    {
        $user = $request->user()->load('profile.socialLinks');

        // ✅ Ensure profile exists
        if (!$user->profile) {
            $user->profile()->create([
                'bio' => '',
                'phone' => '',
                'website' => '',
                'location' => '',
            ]);
            $user->load('profile.socialLinks'); // reload with newly created profile
        }

        return response()->json($user);
    }

    // Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }
}
