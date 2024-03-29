<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpres\ResponseFormatter;
use Laravel\Fortify\Rules\Password;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use PhpParser\Node\Stmt\Return_;

class UserController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' =>['required', 'string', 'max:225'],
                'username' =>['required', 'string', 'max:225', 'unique:users'],
                'email' =>['required', 'string', 'email', 'max:225', 'unique:users'],
                'phone' =>['nullable', 'string', 'max:225'],
                'password' =>['required', 'string', new Password],
            ]);

            User::create([
                'name' => $request->name,
                'unsername' => $request->unsername,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
            ]);

            $user = User::where('email', $request->email)->first();

            $tokenResult = $user->createToken('authToken')->plainTextToken;

            return ResponseFormatter::success([
                'access_token' => $tokenResult,
                'token_type' => 'Bearer',
                'user' => $user
            ], 'User Registered');
        }catch (Exception $error) {
            return ResponseFormatter::success([
                'message' => 'Shomething went worng',
                'error' => $error
            ], 'Authentication Failed', 500);
        }
    }

    public function login(Request $request)
    {
       try {
        $request->validate([
            'email' => 'email|required',
            'password' => 'required'
        ]);

        $credentials = request(['email','password']);
        if(!Auth::attempt($credentials)){
            return ResponseFormatter::error([
                'message' => 'Unauthorized'
            ], 'Authentication Failed', 500);
        }

        $user = User::where('email', $request->email)->first();

        if(! Hash::check($request->password, $user->password, [])) {
            throw new \Exception('Invalid Credentials');
        }

        $tokenResult = $user->createToken('authToken')->plainTextToken;
        return ResponseFormatter::success([
            'access_token' => $tokenResult,
            'token_type' => 'Bearer',
            'user' => $user
        ], 'Authenticated');
       } catch (Exception $error) {
        return ResponseFormatter::error([
            'message' => 'Something went wrong',
            'error' => $error
        ],'Authentication Failed', 500);
       } 
    }

    public function fetch(Request $request)
    {
        return ResponseFormatter::success($request->user(),'Data profil user berhasil di ambil');
    }
    public function updatePofil(Request $request)
    {
        $data = $request->all();

        $user = Auth::user();
        $user->update($data);
        return ResponseFormatter::success($user, 'profile update');
    }
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken()->delete();
        return ResponseFormatter::success($token,'Token Revoked');
    }
}