<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Register
    public function register(Request $req)
    {
        $data = $req->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:4',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // If you use tymon/jwt-auth (JWT), create token like this:
        $token = auth()->login($user); // returns JWT token string

        return response()->json([
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // Login
    public function login(Request $req)
    {
        // $credentials = $req->validate([
        //     'email' => 'required|email',
        //     'password' => 'required|string',
        // ]);

        $credentials = $req->only('email', 'password');

        if (! $token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Invalid Credentials'], 401);
        }

        return response()->json([
            'user' => auth()->user(),
            'token' => $token,
        ]);
    }

    // current user
    public function me()
    {
        return response()->json(auth()->user());
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        $newToken = auth()->refresh();

        return response()->json([
            'token' => $newToken,
        ]);
    }
}
