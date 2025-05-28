<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
          'id' => $this->Id,
          'name' => $this->Name,
          'avatar_path' => $this->AvatarPath,
          'email' => $this->Email,
          'bio' => $this->Bio,
          'registered_at' => $this->RegisteredAt ? (string) $this->RegisteredAt : null,

          'photos' => PhotoResource::collection($this->whenLoaded('photos')),
          'comments' => CommentResource::collection($this->whenLoaded('comments')),
          'contests' => ContestResource::collection($this->whenLoaded('contests')),
          'contacts' => ContactResource::collection($this->whenLoaded('contacts')),
          'city' => new CityResource($this->whenLoaded('city')),
        ];
    }
}
