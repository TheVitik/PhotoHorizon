<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\PhotoResource;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Отримати всі категорії
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Category::query();

            // Пошук по назві
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('Name', 'CONTAINS', $search);
            }

            $categories = $query->get();

            return response()->json([
              'success' => true,
              'data' => $categories
            ]);

        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при отриманні категорій',
              'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отримати фотографії категорії
     */
    public function getCategoryPhotos(Request $request, string $id): JsonResponse
    {
        try {
            $category = Category::findUuid($id);

            if (!$category) {
                return response()->json([
                  'success' => false,
                  'message' => 'Категорію не знайдено'
                ], 404);
            }

            $query = $category->photos()->with(['user']);

            // Сортування
            $sortBy = $request->get('sort_by', 'CreationDate');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $photos = $query->get();

            return response()->json([
              'success' => true,
              'data' => PhotoResource::collection($photos),
              'category' => new CategoryResource($category)
            ]);

        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при отриманні фотографій категорії',
              'error' => $e->getMessage()
            ], 500);
        }
    }
}