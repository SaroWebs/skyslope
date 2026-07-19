<?php

namespace App\Http\Resources\CustomerApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItineraryStopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tour_id' => $this->tour_id,
            'place_id' => $this->place_id,
            'day_number' => (int) ($this->day_number ?? $this->day_index ?? 0),
            'day_index' => (int) ($this->day_index ?? $this->day_number ?? 0),
            'time' => $this->time,
            'title' => $this->title,
            'description' => $this->description,
            'details' => $this->details,
            'activities' => $this->activities ?? [],
            'accommodation' => $this->accommodation,
            'meals_included' => $this->meals_included ?? [],
            'distance_km' => $this->distance_km === null ? null : (float) $this->distance_km,
            'place' => new PlaceSummaryResource($this->whenLoaded('place')),
            'media' => $this->place && $this->place->relationLoaded('media')
                ? PlaceMediaResource::collection($this->place->media)
                : [],
        ];
    }
}
