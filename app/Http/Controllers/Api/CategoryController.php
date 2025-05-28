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
            $categories = Category::all();

            if ($request->has('search')) {
                $search = mb_strtolower($request->get('search'));

                $categories = $categories->filter(function ($category) use ($search) {
                    return str_contains(mb_strtolower($category->Name), $search);
                })->values();
            }

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

            $photos = $category->photos()->with(['user'])->get();

            $sortBy = $request->get('sort_by', 'CreationDate');
            $sortOrder = strtolower($request->get('sort_order', 'desc'));

            if ($sortOrder === 'asc') {
                $photos = $photos->sortBy(function ($photo) use ($sortBy) {
                    return $photo->$sortBy;
                })->values();
            } else {
                $photos = $photos->sortByDesc(function ($photo) use ($sortBy) {
                    return $photo->$sortBy;
                })->values();
            }

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