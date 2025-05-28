<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class AuthController extends Controller
{

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
          'name' => 'required|string|max:255',
          'email' => 'required|string|email|max:255|unique:users,email',
          'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
              'success' => false, 'errors' => $validator->errors(),
            ], 422);
        }

        $userId = (string) Str::uuid();
        $passwordHash = Hash::make($request->password);

        $user = User::create([
          'Id' => $userId,
          'Name' => $request->name,
          'Email' => $request->email,
          'AvatarPath' => "https://ui-avatars.com/api/?name=".$request->name,
          'PasswordHash' => $passwordHash,
          'RegisteredAt' => Carbon::now(),
        ]);

        $userAuth = UserAuth::create([
          'id' => $userId,
          'email' => $request->email,
          'password' => $passwordHash,
        ]);

        $token = $userAuth->createToken('auth_token')->plainTextToken;

        return response()->json([
          'success' => true,
          'token' => $token,
          'user' => new UserResource($user),
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
          'email' => 'required|email',
          'password' => 'required',
        ]);

        $userAuth = UserAuth::where('email', $request->email)->first();

        if (!$userAuth || !Hash::check($request->password, $userAuth->password)) {
            return response()->json([
              'success' => false,
              'message' => 'Неправильна пошта або пароль',
            ], 401);
        }

        $token = $userAuth->createToken('auth_token')->plainTextToken;

        $user = User::findUuid($userAuth->id);

        return response()->json([
          'success' => true,
          'token' => $token,
          'user' => new UserResource($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
          'success' => true,
          'message' => 'Вихід виконано успішно',
        ]);
    }

}
