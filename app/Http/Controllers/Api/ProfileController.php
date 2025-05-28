<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ContactResource;
use App\Http\Resources\UserResource;
use App\Models\City;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProfileController
{

    public function me(): JsonResponse
    {
        $authUser = auth()->user();
        $user = User::with([
          'contacts',
          'city',
        ])->findUuid($authUser->id);

        if (!$user) {
            return response()->json([
              'success' => false,
              'message' => 'Користувача не знайдено',
            ], 404);
        }

        return response()->json(new UserResource($user));
    }

    public function update(Request $request): JsonResponse
    {
        $authUser = auth()->user();
        $user = User::with(['city'])->findUuid($authUser->id);

        if (!$user) {
            return response()->json([
              'success' => false,
              'message' => 'Користувача не знайдено',
            ], 404);
        }

        $validated = $request->validate([
          'name' => 'required|string|max:255',
          'bio' => 'nullable|string',
          'city' => 'required|string|max:64',
        ]);

        $city = City::findUuid($validated['city']);
        if (!$city) {
            return response()->json([
              'success' => false,
              'message' => 'Місто не знайдено',
            ], 404);
        }

        $user->Name = $validated['name'];
        $user->Bio = $validated['bio'];
        $user->city()->attach($city);
        $user->save();

        $user->load('city');

        return response()->json(new UserResource($user));
    }

    /**
     * Додати контактні дані
     */
    public function addContact(Request $request): JsonResponse
    {
        $authUser = auth()->user();
        $user = User::findUuid($authUser->id);

        if (!$user) {
            return response()->json([
              'success' => false,
              'message' => 'Користувача не знайдено',
            ], 404);
        }

        $validated = $request->validate([
          'type' => 'required|string|in:Telegram,Instagram,WhatsApp',
          'handle' => 'required|string|max:255',
        ]);

        $contact = new Contact([
          'Type' => $validated['type'],
          'Handle' => $validated['handle'],
          'Id' => (string) Str::uuid(),
        ]);
        $contact->save();

        $user->contacts()->attach($contact);

        return response()->json([
          'success' => true,
          'data' => new ContactResource($contact),
          'message' => 'Контакт додано успішно',
        ]);
    }

    /**
     * Видалити контактні дані
     */
    public function removeContact($contactId): JsonResponse
    {
        $authUser = auth()->user();
        $user = User::findUuid($authUser->id);
        $contact = Contact::findUuid($contactId);

        if (!$user || !$contact) {
            return response()->json([
              'success' => false,
              'message' => 'Користувача або контакт не знайдено',
            ], 404);
        }

        $user->contacts()->detach($contact);
        $contact->delete();

        return response()->json([
          'success' => true,
          'message' => 'Контакт видалено успішно',
        ]);
    }

}