<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContestResource;
use App\Http\Resources\PhotoResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\Photo;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{

    /**
     * Отримати список всіх користувачів
     */
    public function index(): JsonResponse
    {
        $users = User::with(['city', 'contacts', 'photos'])->get();

        // TODO: Пошук по City, Country, Region

        // TODO: Order By Desc Likes

        return response()->json([
          'success' => true,
          'data' => UserResource::collection($users),
        ]);
    }

    /**
     * Отримати профіль користувача
     */
    public function show($id): JsonResponse
    {
        $user = User::with(['city.region.country', 'contacts'])
          ->findUuid($id);

        if (!$user) {
            return response()->json([
              'success' => false,
              'message' => 'Користувача не знайдено',
            ], 404);
        }

        return response()->json([
          'success' => true,
          'data' => new UserResource($user),
        ]);
    }

    /**
     * Оновити профіль користувача
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findUuid($id);

        if (!$user) {
            return response()->json([
              'success' => false,
              'message' => 'Користувача не знайдено',
            ], 404);
        }

        $validated = $request->validate([
          'name' => 'sometimes|string|max:255',
          'bio' => 'sometimes|string|max:500',
          'email' => 'sometimes|email|unique:users,email,'.$id,
        ]);

        $user->update($validated);

        return response()->json([
          'success' => true,
          'data' => new UserResource($user),
          'message' => 'Профіль оновлено успішно',
        ]);
    }

    /**
     * Отримати фотографії користувача
     */
    public function getUserPhotos($id): JsonResponse
    {
        $user = User::findUuid($id);

        if (!$user) {
            return response()->json([
              'success' => false,
              'message' => 'Користувача не знайдено',
            ], 404);
        }

        $photos = $user->photos()->with([
          'categories', 'comments.user', 'contests',
        ])->get();

        return response()->json([
          'success' => true,
          'data' => PhotoResource::collection($photos),
        ]);
    }

    /**
     * Отримати конкурси користувача
     */
    public function getUserContests($id): JsonResponse
    {
        $user = User::findUuid($id);

        if (!$user) {
            return response()->json([
              'success' => false,
              'message' => 'Користувача не знайдено',
            ], 404);
        }

        $contests = $user->contests()->with(['photos.user'])->get();

        return response()->json([
          'success' => true,
          'data' => ContestResource::collection($contests),
        ]);
    }

    public function followUser(Request $request, $id): JsonResponse
    {
        $user = auth()->user();

        $currentUser = User::findUuid($user->id);
        $toFollow = User::findUuid($id);

        if (!$toFollow) {
            return response()->json([
              'success' => false, 'message' => 'Користувач не знайдений',
            ], 404);
        }

        // Створити зв'язок, якщо його немає
        $currentUser->follow($toFollow);

        return response()->json([
          'success' => true, 'message' => 'Підписка додана',
        ]);
    }

    public function unfollowUser(Request $request, $id): JsonResponse
    {
        $user = auth()->user();

        $currentUser = User::findUuid($user->id);
        $toUnfollow = User::findUuid($id);

        if (!$toUnfollow) {
            return response()->json([
              'success' => false, 'message' => 'Користувач не знайдений',
            ], 404);
        }

        // Видалити зв'язок, якщо існує
        $currentUser->unfollow($toUnfollow);

        return response()->json([
          'success' => true, 'message' => 'Підписка видалена',
        ]);
    }

    public function getFollowers(Request $request, $id): JsonResponse
    {
        $user = User::findUuid($id);

        if (!$user) {
            return response()->json([
              'success' => false, 'message' => 'Користувач не знайдений',
            ], 404);
        }

        $followers = $user->followers()->get();

        return response()->json(['success' => true, 'data' => UserResource::collection($followers)]);
    }

    public function getFollowing(Request $request, $id): JsonResponse
    {
        $user = User::findUuid($id);

        if (!$user) {
            return response()->json([
              'success' => false, 'message' => 'Користувач не знайдений',
            ], 404);
        }

        $following = $user->following();

        return response()->json(['success' => true, 'data' => UserResource::collection($following)]);
    }

}