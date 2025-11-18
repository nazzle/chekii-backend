<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
            'location_id' => 'required'
        ]);

        $user = User::where('username', $request->username)->first();
        $location = Location::where('id', $request->location_id)->first();

        if (! $user || ! Hash::check($request->password, $user->password) || ! $user->active) {
            return response()->json(
                [
                    'message' => 'Invalid credentials',
                    'status' => false,
                    'code' => config('httpStatus.BAD_REQUEST')
                ],
                401);
        }

        if (! $location) {
            return response()->json(
                [
                    'message' => 'Selected Location is not valid',
                    'status' => false,
                    'code' => config('httpStatus.BAD_REQUEST')
                ],
                401);
        }

//        $token = $user->createToken('frontend')->plainTextToken;
        $token = $user->createToken('api');

        //  Setting expiry time
        $token->accessToken->expires_at = now()->addMinutes(59);
        $token->accessToken->save();

        //  Getting token
        $plainTextToken = $token->plainTextToken;
        $delimiter = "|";
        $tokenValue = Str::after($plainTextToken, $delimiter);

        // Getting user roles
        $user->load('roles');
        $user->roles->load('permissions');

        $auth_object = [
            'username' => $user->username,
            'access_token' => $tokenValue,
            'expires_at' => $token->accessToken->expires_at,
            'roles' => $user->roles,
            'location' => $location
        ];
        return response()->json([
            'auth_object' => $auth_object
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(
            [
                'message' => 'User logged out',
                'status' => true,
                'code' => config('httpStatus.OK')
            ]
        );
    }

}
