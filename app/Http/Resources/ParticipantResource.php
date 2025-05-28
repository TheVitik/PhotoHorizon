<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ParticipantResource extends JsonResource
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
          'city' => $this->whenLoaded('city', $this->city->Name),
        ];
    }
}
