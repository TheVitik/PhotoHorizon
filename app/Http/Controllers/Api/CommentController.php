<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\User;
use App\Models\Photo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{

    /**
     * Створити новий коментар
     */
    public function store(Request $request, string $photoId): JsonResponse
    {
        try {
            $validated = $request->validate([
              'text' => 'required|string|max:1000',
            ]);

            $photo = Photo::findUuid($photoId);

            if (!$photo) {
                return response()->json([
                  'success' => false,
                  'message' => 'Фото не знайдено',
                ], 404);
            }

            // Створюємо коментар
            $comment = Comment::create([
              'Id' => (string) Str::uuid(),
              'Text' => $validated['text'],
            ]);

            // Створюємо зв'язки
            $authUser = auth()->user();
            $user = User::findUuid($authUser->id);
            $photo = Photo::findUuid($photoId);

            if (!$user) {
                return response()->json([
                  'success' => false,
                  'message' => 'Користувача не знайдено',
                ], 404);
            }

            if (!$photo) {
                return response()->json([
                  'success' => false,
                  'message' => 'Фото не знайдено',
                ], 404);
            }

            $comment->user()->attach($user);
            $comment->photo()->attach($photo);
            $comment->load(['user', 'photo']);

            return response()->json([
              'success' => true,
              'message' => 'Коментар успішно створено',
              'data' => new CommentResource($comment),
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка валідації',
              'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при створенні коментаря',
              'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Оновити коментар
     */
    public function update(
      Request $request,
      string $photoId,
      string $commentId
    ): JsonResponse {
        try {
            $comment = Comment::findUuid($commentId);

            if (!$comment) {
                return response()->json([
                  'success' => false,
                  'message' => 'Коментар не знайдено',
                ], 404);
            }

            $validated = $request->validate([
              'text' => 'required|string|max:1000',
            ]);

            $comment->update([
              'Text' => $validated['text'],
            ]);

            $comment->load(['user', 'photo']);

            return response()->json([
              'success' => true,
              'message' => 'Коментар успішно оновлено',
              'data' => new CommentResource($comment),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка валідації',
              'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при оновленні коментаря',
              'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Видалити коментар
     */
    public function destroy(string $photoId, string $commentId): JsonResponse
    {
        try {
            $comment = Comment::findUuid($commentId);

            if (!$comment) {
                return response()->json([
                  'success' => false,
                  'message' => 'Коментар не знайдено',
                ], 404);
            }

            $comment->delete();

            return response()->json([
              'success' => true,
              'message' => 'Коментар успішно видалено',
            ]);
        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при видаленні коментаря',
              'error' => $e->getMessage(),
            ], 500);
        }
    }

}