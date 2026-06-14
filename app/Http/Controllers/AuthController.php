<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:BUYER,SELLER,DRIVER',
        ]);

        // Find the role
        $role = Role::where('name', $request->role)->firstOrFail();

        // Create the user with that role as active
        $user = User::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'active_role_id' => $role->id,
        ]);

        // Attach to user_roles pivot table
        $user->roles()->attach($role->id);

        $token = $user->createToken('android-app')->plainTextToken;

        return response()->json([
            'user' => $user->load('activeRole', 'roles'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken('android-app')->plainTextToken;

        return response()->json([
            'user' => $user->load('activeRole', 'roles'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json(
            $request->user()->load('activeRole', 'roles')
        );
    }

    // Switch active role (user can have multiple roles)
    public function switchRole(Request $request)
    {
        $request->validate([
            'role' => 'required|in:BUYER,SELLER,DRIVER',
        ]);

        $user = $request->user();
        $role = Role::where('name', $request->role)->firstOrFail();

        // Make sure user actually has this role
        if (!$user->roles->contains($role->id)) {
            return response()->json([
                'message' => 'You do not have this role',
            ], 403);
        }

        $user->update(['active_role_id' => $role->id]);

        return response()->json([
            'user' => $user->load('activeRole', 'roles'),
        ]);
    }

    public function addRole(Request $request)
    {
        $request->validate([
            'role' => 'required|in:BUYER,SELLER,DRIVER',
        ]);

        $user = $request->user();
        $role = Role::where('name', $request->role)->firstOrFail();

        // Check if user already has this role
        if ($user->roles->contains($role->id)) {
            return response()->json([
                'message' => 'You already have this role',
            ], 409);
        }

        $user->roles()->attach($role->id);

        return response()->json([
            'message' => 'Role added successfully',
            'user' => $user->load('activeRole', 'roles'),
        ]);
    }
}
