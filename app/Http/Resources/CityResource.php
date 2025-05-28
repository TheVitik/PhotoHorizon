<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
          "id" => $this->Id,
          "latitude" => $this->Latitude,
          "longitude" => $this->Longitude,
          "name" => $this->Name,
          "region" => new RegionResource($this->whenLoaded('region')),
          "country" => new CountryResource($this->whenLoaded('country')),
        ];
    }

}
