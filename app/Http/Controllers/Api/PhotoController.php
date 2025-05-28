<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhotoResource;
use App\Models\Photo;
use App\Models\User;
use App\Models\Category;
use App\Models\Contest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laudis\Neo4j\ClientBuilder;

class PhotoController extends Controller
{

    /**
     * Отримати всі фотографії з фільтрацією
     */
    public function index(Request $request): JsonResponse
    {
        $query = Photo::with([
          'user', 'categories', 'comments.user', 'contests',
        ]);

        // Фільтр за категорією
        if ($request->has('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('id', $request->category_id);
            });
        }

        // Фільтр за користувачем
        if ($request->has('user_id')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('id', $request->user_id);
            });
        }

        // Фільтр за конкурсом
        if ($request->has('contest_id')) {
            $query->whereHas('contests', function ($q) use ($request) {
                $q->where('id', $request->contest_id);
            });
        }

        // Сортування
        $sortBy = $request->get('sort_by', 'creation_date');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'likes') {
            $query->orderBy('likes_count', $sortOrder);
        } else {
            $query->orderBy('creation_date', $sortOrder);
        }

        $photos = $query->get();

        return response()->json([
          'success' => true,
          'data' => PhotoResource::collection($photos),
        ]);
    }

    /**
     * Отримати конкретну фотографію
     */
    public function show($id): JsonResponse
    {
        $photo = Photo::with(['user', 'categories', 'comments.user'])
          ->findUuid($id);

        if (!$photo) {
            return response()->json([
              'success' => false,
              'message' => 'Фотографію не знайдено',
            ], 404);
        }

        return response()->json([
          'success' => true,
          'data' => new PhotoResource($photo),
        ]);
    }

    /**
     * Завантажити нову фотографію
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
          'photo' => 'required|image|max:20480',
          'description' => 'required|string|max:500',
          'location_latitude' => 'nullable|numeric|between:-90,90',
          'location_longitude' => 'nullable|numeric|between:-180,180',
          'category_ids' => 'array',
          'category_ids.*' => 'string',
        ]);

        $existingCategoryIds = Category::whereIn('Id',
          $validated['category_ids'])->get()->pluck('Id')->toArray();
        $invalidCategoryIds = array_diff($validated['category_ids'],
          $existingCategoryIds);

        if (count($invalidCategoryIds) > 0) {
            return response()->json([
              'success' => false,
              'message' => 'Некоректні ідентифікатори категорій: '.implode(', ',
                  $invalidCategoryIds),
            ], 422);
        }

        $photoFile = $request->file('photo');
        $filename = time().'_'.uniqid().'.'.$photoFile->getClientOriginalExtension();
        $path = $photoFile->storeAs('photos', $filename, 'public');

        $publicPath = 'storage/'.$path;

        $photo = new Photo([
          'Id' => (string) Str::uuid(),
          'Path' => $publicPath,
          'Description' => $validated['description'],
          'LocationLatitude' => $validated['location_latitude'] ?? null,
          'LocationLongitude' => $validated['location_longitude'] ?? null,
          'LikesCount' => 0,
          'CreationDate' => now(),
        ]);

        $photo->save();

        // Пов'язати з користувачем
        $authUser = auth()->user();
        $user = User::findUuid($authUser->id);
        $user->photos()->attach($photo);

        // Пов'язати з категоріями
        if (!empty($validated['category_ids'])) {
            foreach ($validated['category_ids'] as $categoryId) {
                $category = Category::findUuid($categoryId);
                if ($category) {
                    $photo->categories()->attach($category);
                }
            }
        }

        $photo->load(['user', 'categories']);

        return response()->json([
          'success' => true,
          'data' => new PhotoResource($photo),
          'message' => 'Фотографію завантажено успішно',
        ], 201);
    }

    /**
     * Оновити фотографію
     */
    public function update(Request $request, $id): JsonResponse
    {
        $photo = Photo::findUuid($id);

        if (!$photo) {
            return response()->json([
              'success' => false,
              'message' => 'Фотографію не знайдено',
            ], 404);
        }

        $validated = $request->validate([
          'description' => 'sometimes|string|max:500',
          'location_latitude' => 'nullable|numeric|between:-90,90',
          'location_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $photo->update([
          'Description' => $validated['description'],
          'LocationLatitude' => $validated['location_latitude'] ?? null,
          'LocationLongitude' => $validated['location_longitude'] ?? null,
        ]);

        return response()->json([
          'success' => true,
          'data' => new PhotoResource($photo),
          'message' => 'Фотографію оновлено успішно',
        ]);
    }

    /**
     * Видалити фотографію
     */
    public function destroy($id): JsonResponse
    {
        $photo = Photo::findUuid($id);

        if (!$photo) {
            return response()->json([
              'success' => false,
              'message' => 'Фотографію не знайдено',
            ], 404);
        }

        $client = ClientBuilder::create()
          ->withDriver('bolt', 'bolt://neo4j:11111111@localhost:7687')
          ->build();

        $client->run(
          'MATCH (p:Photo {Id: $id})-[r]-() DELETE r',
          ['id' => $photo->Id]
        );

        $client->run(
          'MATCH (p:Photo {Id: $id}) DELETE p',
          ['id' => $photo->Id]
        );

        return response()->json([
          'success' => true,
          'message' => 'Фотографію видалено успішно',
        ]);
    }

    /**
     * Лайкнути фотографію
     */
    public function like($id): JsonResponse
    {
        $user = auth()->user();

        $photo = Photo::findUuid($id);

        if (!$photo) {
            return response()->json([
              'success' => false,
              'message' => 'Фотографію не знайдено',
            ], 404);
        }

        $key = 'like_user_id_'.$user->id.'_photo_id_'.$photo->id;
        if (Cache::get($key, false) === true) {
            return response()->json([
              'success' => false,
              'message' => 'Вам вже подобається це фото',
            ], 422);
        }

        $photo->likes_count += 1;
        $photo->save();

        Cache::put($key, true, now()->addMinutes(1440));

        return response()->json([
          'success' => true,
          'data' => ['likes_count' => $photo->likes_count],
          'message' => 'Лайк додано',
        ]);
    }

    /**
     * Дизлайкнути фотографію
     */
    public function dislike($id): JsonResponse
    {
        $user = auth()->user();

        $photo = Photo::findUuid($id);

        if (!$photo) {
            return response()->json([
              'success' => false,
              'message' => 'Фотографію не знайдено',
            ], 404);
        }

        $key = 'like_user_id_'.$user->id.'_photo_id_'.$photo->id;
        if (Cache::get($key, true) === false) {
            return response()->json([
              'success' => false,
              'message' => 'Вам вже не подобається це фото',
            ], 422);
        }

        $photo->likes_count -= 1;
        $photo->save();

        Cache::put($key, false, now()->addMinutes(1440));

        return response()->json([
          'success' => true,
          'data' => ['likes_count' => $photo->likes_count],
          'message' => 'Дизлайк додано',
        ]);
    }

    /**
     * Додати фотографію до конкурсу
     */
    public function addToContest(Request $request, $photoId, $contestId): JsonResponse
    {
        $photo = Photo::findUuid($photoId);

        if (!$photo) {
            return response()->json([
              'success' => false,
              'message' => 'Фотографію не знайдено',
            ], 404);
        }

        $contest = Contest::findUuid($contestId);

        if(!$contest) {
            return response()->json([
              'success' => false,
              'message' => 'Конкурс не знайдено',
            ], 404);
        }

        // Перевірити чи конкурс ще активний
        if ($contest->EndDateTime < now()) {
            return response()->json([
              'success' => false,
              'message' => 'Конкурс вже завершено',
            ], 400);
        }

        $photo->contests()->attach($contest);

        return response()->json([
          'success' => true,
          'message' => 'Фотографію додано до конкурсу',
        ]);
    }

    /**
     * Отримати фотографії по локації
     */
    public function getByLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
          'latitude' => 'required|numeric|between:-90,90',
          'longitude' => 'required|numeric|between:-180,180',
          'radius' => 'nullable|numeric|min:0.1|max:100',
        ]);

        $radius = $validated['radius'] ?? 10;
        $lat = $validated['latitude'];
        $lng = $validated['longitude'];

        // Простий пошук в радіусі
        $photos = Photo::with(['user', 'categories', 'comments.user'])
          ->whereNotNull('LocationLatitude')
          ->whereNotNull('LocationLongitude')
          ->get()
          ->filter(function ($photo) use ($lat, $lng, $radius) {
              $distance = $this->calculateDistance(
                $lat, $lng,
                $photo->LocationLatitude,
                $photo->LocationLongitude
              );
              return $distance <= $radius;
          });

        return response()->json([
          'success' => true,
          'data' => $photos->values(),
        ]);
    }

    /**
     * Розрахувати відстань між двома точками (формула Haversine)
     */
    private function calculateDistance($lat1, $lng1, $lat2, $lng2): float
    {
        $earthRadius = 6371; // км

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
          cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
          sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

}