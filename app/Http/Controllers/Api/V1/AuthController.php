<?php
// app/Http/Controllers/Api/V1/AuthController.php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AktivitasUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    // REGISTER (hanya untuk testing, production mungkin disable)
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'sometimes|in:owner,admin,gudang'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'gudang',
        ]);

        // Log aktivitas
        AktivitasUser::log($user, 'register', 'User', $user->id, "User {$user->name} registered");

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    // LOGIN
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where(['email' => $request->email])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Log aktivitas
        AktivitasUser::log($user, 'login', 'User', $user->id, "User {$user->name} logged in");

        // Revoke old tokens (optional)
        // $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // LOGOUT
    public function logout(Request $request)
    {
        $user = $request->user();
        
        AktivitasUser::log($user, 'logout', 'User', $user->id, "User {$user->name} logged out");
        
        // Cara alternatif yang lebih aman dari error IDE
        if ($request->user() && $request->user()->currentAccessToken()) {
            $request->user()->tokens()->where('id', $request->user()->currentAccessToken()->id)->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    // GET CURRENT USER
    public function me(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ]
        ]);
    }

    // REFRESH TOKEN (logout old, create new)
    public function refresh(Request $request)
    {
        $user = $request->user();
        
        // Revoke old token
        if ($user && $user->currentAccessToken()) {
            $user->tokens()->where('id', $user->currentAccessToken()->id)->delete();
        }
        
        $token = $user->createToken('auth_token')->plainTextToken;
        
        return response()->json([
            'message' => 'Token refreshed',
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    // CHANGE PASSWORD
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect'
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Log aktivitas
        AktivitasUser::log($user, 'change_password', 'User', $user->id, "User {$user->name} changed password");

        return response()->json([
            'message' => 'Password changed successfully'
        ]);
    }
}