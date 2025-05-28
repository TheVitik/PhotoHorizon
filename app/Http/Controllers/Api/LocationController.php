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
            $countries = Country::with(['regions.cities'])->get();

            if ($request->has('search')) {
                $search = mb_strtolower($request->get('search'));
                $countries = $countries->filter(function ($country) use ($search) {
                    return mb_stripos($country->Name, $search) !== false;
                });
            }

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

            $users = $city->users;

            // Фільтрація активних користувачів (ті, що мають фото або брали участь у конкурсах)
            if ($request->has('active_only')) {
                $users = $users->filter(function ($user) {
                    return $user->photos->isNotEmpty() || $user->contests->isNotEmpty();
                });
            }

            // Пошук по імені (case-insensitive)
            if ($request->has('search')) {
                $search = mb_strtolower($request->get('search'));
                $users = $users->filter(function ($user) use ($search) {
                    return mb_stripos($user->Name, $search) !== false;
                });
            }

            // Сортування (за замовчуванням по 'Name' ASC)
            $sortBy = $request->get('sort_by', 'Name');
            $sortOrder = strtolower($request->get('sort_order', 'asc'));

            $users = $users->sortBy(function ($user) use ($sortBy) {
                return $user->{$sortBy} ?? '';
            }, SORT_REGULAR, $sortOrder === 'desc');

            $users = $users->values();

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

            $users = User::with(['photos', 'contests', 'city'])->whereHas('city',function ($query) use ($cityIds) {
                $query->whereIn('Id', $cityIds);
            })->get();

            // Фільтрація користувачів
            if ($request->has('active_only')) {
                // Припускаємо, що активні користувачі мають фото або брали участь у конкурсах
                $users = $users->filter(function ($user) {
                    return $user->photos->isNotEmpty() || $user->contests->isNotEmpty();
                });
            }

            // Пошук по імені
            if ($request->has('search')) {
                $search = mb_strtolower($request->get('search'));
                $users = $users->filter(function ($user) use ($search) {
                    return mb_stripos($user->Name, $search) !== false;
                });
            }

            // Сортування
            $sortBy = $request->get('sort_by', 'Name');
            $sortOrder = strtolower($request->get('sort_order', 'asc'));

            $users = $users->sortBy(function ($user) use ($sortBy) {
                return $user->{$sortBy} ?? '';
            }, SORT_REGULAR, $sortOrder === 'desc');

            $users = $users->values();

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