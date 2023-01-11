<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

/**
 * @group Auth
 *
 * Bearer Token authentication.
 *
 * The authentication uses a bearer token to validate the requests.
 *
 * That means, you can send a request to `/register` endpoint to create a user.
 * After that, login with the user sending a request to `/login` to receive the Bearer Token.
 *
 * The Bearer token must be sent in the `Authorization` header in subsequent requests to endpoints that require authentication.
 *
 */
class AuthController extends Controller
{
    /**
     * Register
     *
     * Register a user for authentication.
     *
     * @unauthenticated
     *
     */
    public function register(Request $request) {
        $fields = $request->validate([
            'name' => ['required', 'string'],
            'email' => ['required', 'string', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email' => $fields['email'],
            'password' => bcrypt($fields['password']),
        ]);

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token,
        ];

        return response($response, 201);
    }

    /**
     * Authenticate
     *
     * Authenticate user and returns a `bearer` token.
     *
     * @unauthenticated
     *
     */
    public function login(Request $request) {
        $fields = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Check email
        $user = User::where('email', $fields['email'])->first();

        // Check password
        if (!$user || !Hash::check($fields['password'], $user->password)){
            return response([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('myapptoken')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token,
        ];

        return response($response, 201);
    }

    /**
     * Logout
     *
     * Invalidate the user's current access token.
     *
     * @response 200 {
            "message": "Logged out"
     * }
     */
    public function logout(Request $request){
        auth()->user()->tokens()->delete();

        return response([
            'message' => 'Logged out'
        ], 200);
    }
}
