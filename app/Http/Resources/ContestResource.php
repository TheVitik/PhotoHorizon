<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ContestResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
          'id' => $this->Id,
          'name' => $this->Name,
          'photo_path' => $this->PhotoPath,
          'description' => $this->Description,
          'start_date_time' => $this->StartDateTime,
          'end_date_time' => $this->EndDateTime,
          'status' => $this->when(!empty($this->status), $this->status),

          'organizers' => $this->whenLoaded('organizers', function () {
              return $this->organizers->map(function ($organizer) {
                  return [
                    'id' => $organizer->Id,
                    'name' => $organizer->Name,
                    'email' => $organizer->Email,
                  ];
              });
          }),

          'photos' => $this->whenLoaded('photos', function () {
              return $this->photos->map(function ($photo) {
                  return [
                    'id' => $photo->Id,
                    'url' => url('storage',$photo->Path),
                    'description' => $photo->Description,
                    'likes_count' => $photo->LikesCount,
                    'user' => new ParticipantResource($photo->user),
                  ];
              });
          }),
        ];
    }
}
