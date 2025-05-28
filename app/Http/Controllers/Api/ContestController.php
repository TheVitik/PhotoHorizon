<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ContestResource;
use App\Http\Resources\ParticipantResource;
use App\Http\Resources\PhotoResource;
use App\Http\Resources\UserCommentResource;
use App\Models\Contest;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ContestController extends Controller
{

    /**
     * Отримати всі конкурси
     */
    public function index(Request $request): JsonResponse
    {
        $query = Contest::with(['organizers', 'photos.user.city']);

        // Фільтр за статусом
        $status = $request->get('status');
        if ($status === 'active') {
            $query->where('StartDateTime', '<=', now())
              ->where('StartDateTime', '>=', now());
        } elseif ($status === 'upcoming') {
            $query->where('StartDateTime', '>', now());
        } elseif ($status === 'finished') {
            $query->where('StartDateTime', '<', now());
        }

        // Фільтр за організатором
        if ($request->has('organizer_id')) {
            $query->whereHas('organizers', function ($q) use ($request) {
                $q->where('id', $request->organizer_id);
            });
        }

        $contests = $query->orderBy('StartDateTime', 'desc')->get();

        /* $contests = $query->orderBy('StartDateTime', 'desc')
           ->paginate($request->get('per_page', 10));*/

        return response()->json([
          'success' => true,
          'data' => ContestResource::collection($contests),
        ]);
    }

    /**
     * Отримати конкретний конкурс
     */
    public function show($id): JsonResponse
    {
        $contest = Contest::with([
          'organizers',
          'photos.user.city',
        ])->findUuid($id);

        if (!$contest) {
            return response()->json([
              'success' => false,
              'message' => 'Конкурс не знайдено',
            ], 404);
        }

        // Додати інформацію про статус конкурсу
        $now = now();
        if ($contest->StartDateTime > $now) {
            $contest->status = 'upcoming';
        } elseif ($contest->EndDateTime < $now) {
            $contest->status = 'finished';
        } else {
            $contest->status = 'active';
        }

        return response()->json([
          'success' => true,
          'data' => new ContestResource($contest),
        ]);
    }

    /**
     * Створити новий конкурс
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
          'photo' => 'required|image|max:20480',
          'name' => 'required|string|max:255',
          'description' => 'required|string|max:1000',
          'start_date_time' => 'required|date|after:now',
          'end_date_time' => 'required|date|after:start_date_time',
        ]);

        $photoFile = $request->file('photo');
        $filename = time().'_'.uniqid().'.'.$photoFile->getClientOriginalExtension();
        $path = $photoFile->storeAs('photos', $filename, 'public');

        $publicPath = 'storage/'.$path;

        $contest = new Contest([
          'Id' => (string) Str::uuid(),
          'Name' => $validated['name'],
          'Description' => $validated['description'],
          'PhotoPath' => $publicPath,
          'StartDateTime' => $validated['start_date_time'],
          'EndDateTime' => $validated['end_date_time'],
        ]);

        $contest->save();

        $authUser = auth()->user();
        $user = User::findUuid($authUser->id);
        if ($user) {
            $user->contests()->attach($contest);
        }

        $contest->load(['organizers']);

        return response()->json([
          'success' => true,
          'data' => new ContestResource($contest),
          'message' => 'Конкурс створено успішно',
        ], 201);
    }

    /**
     * Оновити конкурс
     */
    public function update(Request $request, $id): JsonResponse
    {
        $contest = Contest::findUuid($id);

        if (!$contest) {
            return response()->json([
              'success' => false,
              'message' => 'Конкурс не знайдено',
            ], 404);
        }

        // Перевірити чи конкурс не почався
        if ($contest->StartDateTime <= now() && $contest->EndDateTime < now()) {
            return response()->json([
              'success' => false,
              'message' => 'Неможливо редагувати активний або завершений конкурс',
            ], 400);
        }

        $validated = $request->validate([
          'name' => 'sometimes|string|max:255',
          'description' => 'sometimes|string|max:1000',
          'start_date_time' => 'sometimes|date|after:now',
          'end_date_time' => 'sometimes|date|after:start_date_time',
        ]);

        $contest->update([
          'Name' => $validated['name'],
          'Description' => $validated['description'],
          'StartDateTime' => $validated['start_date_time'],
          'EndDateTime' => $validated['end_date_time'],
        ]);

        return response()->json([
          'success' => true,
          'data' => new ContestResource($contest),
          'message' => 'Конкурс оновлено успішно',
        ]);
    }

    /**
     * Видалити конкурс
     */
    public function destroy($id): JsonResponse
    {
        $contest = Contest::findUuid($id);

        if (!$contest) {
            return response()->json([
              'success' => false,
              'message' => 'Конкурс не знайдено',
            ], 404);
        }

        // Перевірити чи конкурс не почався
        if ($contest->StartDateTime <= now()) {
            return response()->json([
              'success' => false,
              'message' => 'Неможливо видалити активний або завершений конкурс',
            ], 400);
        }

        // Видалити всі зв'язки
        $contest->organizers()->detach();
        $contest->photos()->detach();

        $contest->delete();

        return response()->json([
          'success' => true,
          'message' => 'Конкурс видалено успішно',
        ]);
    }

    /**
     * Отримати учасників конкурсу
     */
    public function getParticipants($id): JsonResponse
    {
        $contest = Contest::findUuid($id);

        if (!$contest) {
            return response()->json([
              'success' => false,
              'message' => 'Конкурс не знайдено',
            ], 404);
        }

        $participants = $contest->photos()
          ->with(['user.city'])
          ->get()
          ->pluck('user')
          ->unique('id')
          ->values();

        return response()->json([
          'success' => true,
          'data' => ParticipantResource::collection($participants),
        ]);
    }

    /**
     * Отримати статистику конкурсу
     */
    public function getStats($id): JsonResponse
    {
        $contest = Contest::with(['photos.user', 'photos.comments.user'])
          ->findUuid($id);

        if (!$contest) {
            return response()->json([
              'success' => false,
              'message' => 'Конкурс не знайдено',
            ], 404);
        }

        $stats = [
          'total_photos' => $contest->photos->count(),
          'total_participants' => $contest->photos->pluck('user')
            ->unique('id')
            ->count(),
          'total_likes' => $contest->photos->sum('likes_count'),
          'total_comments' => $contest->photos->sum(function ($photo) {
              return $photo->comments->count();
          }),
          'most_liked_photo' => new PhotoResource($contest->photos->sortByDesc('likes_count')
            ->first()),
          'most_active_user' => $contest->photos
            ->groupBy('user.id')
            ->map(function ($photos) {
                return [
                  'user' => new UserCommentResource($photos->first()->user),
                  'photo_count' => $photos->count(),
                ];
            })
            ->sortByDesc('photo_count')
            ->first(),
        ];

        return response()->json([
          'success' => true,
          'data' => $stats,
        ]);
    }

    /**
     * Завершити конкурс та видати нагороди
     */
    public function finishContest($id): JsonResponse
    {
        $contest = Contest::with(['photos'])->findUuid($id);

        if (!$contest) {
            return response()->json([
              'success' => false,
              'message' => 'Конкурс не знайдено',
            ], 404);
        }

        if ($contest->EndDateTime < now()) {
            return response()->json([
              'success' => false,
              'message' => 'Конкурс вже завершився',
            ], 400);
        }

        // Сортуємо фото за кількістю лайків
        $topPhotos = $contest->photos->sortByDesc('likes_count');

        $results = [];

        // Видаємо нагороди топ фотографіям
        foreach ($topPhotos as $index => $photo) {
            $results[] = [
              'photo' => new PhotoResource($photo->load('user')),
              'place' => $index + 1,
            ];
        }

        $contest->EndDateTime = now();
        $contest->save();

        return response()->json([
          'success' => true,
          'data' => $results,
          'message' => 'Конкурс завершено',
        ]);
    }

}