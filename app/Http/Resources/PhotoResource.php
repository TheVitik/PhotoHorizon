<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PhotoResource extends JsonResource
{

    public function toArray($request)
    {
        return [
          'id' => $this->Id,
          'path' => $this->Path,
          'description' => $this->Description,
          'creation_date' => $this->CreationDate,
          'location_latitude' => $this->LocationLatitude,
          'location_longitude' => $this->LocationLongitude,
          'likes_count' => $this->LikesCount,

          'user' => new UserResource($this->whenLoaded('user')),
          'comments' => CommentResource::collection($this->whenLoaded('comments')),
          'categories' => CategoryResource::collection($this->whenLoaded('categories')),
          'contests' => ContestResource::collection($this->whenLoaded('contests')),
        ];
    }

}
