<?php

namespace App\Http\Resources\CustomerApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlaceSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'location' => $this->location,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'rating' => $this->rating === null ? null : (float) $this->rating,
            'review_count' => (int) ($this->review_count ?? 0),
            'cover_image' => $this->cover_image,
            'tags' => $this->tags ?? [],
            'media' => PlaceMediaResource::collection($this->whenLoaded('media')),
        ];
    }
}
