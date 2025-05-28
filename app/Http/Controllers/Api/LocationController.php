<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\RegionResource;
use App\Http\Resources\UserCommentResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\Country;
use App\Models\Region;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class LocationController extends Controller
{
    /**
     * Отримати всі країни
     */
    public function getCountries(Request $request): JsonResponse
    {
        try {
            $query = Country::with(['regions.cities']);

            // Пошук по назві
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('Name', 'CONTAINS', $search);
            }

            $countries = $query->get();

            return response()->json([
              'success' => true,
              'data' => CountryResource::collection($countries)
            ]);

        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при отриманні країн',
              'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отримати регіони країни
     */
    public function getCountryRegions(string $id): JsonResponse
    {
        try {
            $country = Country::with('regions')->findUuid($id);

            if (!$country) {
                return response()->json([
                  'success' => false,
                  'message' => 'Країну не знайдено'
                ], 404);
            }

            return response()->json([
              'success' => true,
              'data' => RegionResource::collection($country->regions),
              'country' => new CountryResource($country)
            ]);

        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при отриманні регіонів країни',
              'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отримати міста регіону
     */
    public function getRegionCities(string $id): JsonResponse
    {
        try {
            $region = Region::with(['cities', 'country'])->findUuid($id);

            if (!$region) {
                return response()->json([
                  'success' => false,
                  'message' => 'Регіон не знайдено'
                ], 404);
            }

            return response()->json([
              'success' => true,
              'data' => CityResource::collection($region->cities),
              'region' => [
                'id' => $region->Id,
                'name' => $region->Name,
                'country' => new CountryResource($region->country)
              ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при отриманні міст регіону',
              'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отримати користувачів міста
     */
    public function getCityUsers(Request $request, string $id): JsonResponse
    {
        try {
            $city = City::with(['region'])->where('Id', $id)->first();

            if (!$city) {
                return response()->json([
                  'success' => false,
                  'message' => 'Місто не знайдено'
                ], 404);
            }

            $query = $city->users();

            // Фільтрація користувачів
            if ($request->has('active_only')) {
                // Припускаємо, що активні користувачі мають фото або брали участь у конкурсах
                $query->where(function ($q) {
                    $q->has('photos')->orHas('contests');
                });
            }

            // Пошук по імені
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('Name', 'CONTAINS', $search);
            }

            // Сортування
            $sortBy = $request->get('sort_by', 'Name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // TODO: Sort by Likes

            $users = $query->get();

            return response()->json([
              'success' => true,
              'data' => UserCommentResource::collection($users),
              'city' => new CityResource($city),
              'users_count' => $users->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при отриманні користувачів міста',
              'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Отримати користувачів країни
     */
    public function getCountryUsers(Request $request, string $id): JsonResponse
    {
        try {
            $country = Country::with(['regions.cities'])->findUuid($id);

            if (!$country) {
                return response()->json([
                  'success' => false,
                  'message' => 'Країну не знайдено'
                ], 404);
            }

            $cityIds = collect();

            foreach ($country->regions as $region) {
                $cityIds = $cityIds->merge($region->cities->pluck('Id'));
            }

            $cityIds = $cityIds->unique()->all();

            //$cityIds = ['ca678c9e-d478-4d6e-a07d-05fba58e24b2'];

            $query = User::query()->whereHas('city',function ($query) use ($cityIds) {
                $query->whereIn('Id', $cityIds);
            });

            // Фільтрація користувачів
            if ($request->has('active_only')) {
                // Припускаємо, що активні користувачі мають фото або брали участь у конкурсах
                $query->where(function ($q) {
                    $q->has('photos')->orHas('contests');
                });
            }

            // Пошук по імені
            if ($request->has('search')) {
                $search = $request->get('search');
                $query->where('Name', 'CONTAINS', $search);
            }

            // Сортування
            $sortBy = $request->get('sort_by', 'Name');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // TODO: Sort by Likes

            $users = $query->get();

            return response()->json([
              'success' => true,
              'data' => UserCommentResource::collection($users),
              'country' => new CountryResource($country),
              'users_count' => $users->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
              'success' => false,
              'message' => 'Помилка при отриманні користувачів країни',
              'error' => $e->getMessage()
            ], 500);
        }
    }
}