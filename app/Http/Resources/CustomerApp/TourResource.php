<?php

namespace App\Http\Resources\CustomerApp;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class TourResource extends JsonResource
{
    public function __construct($resource, protected ?Collection $relatedPlaces = null)
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $nextSchedule = $this->relationLoaded('schedules')
            ? $this->schedules->first()
            : (method_exists($this->resource, 'getNextAvailableSchedule') ? $this->getNextAvailableSchedule() : null);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null),
            'description' => $this->description,
            'short_description' => $this->short_description,
            'highlights' => $this->highlights ?? [],
            'inclusions' => $this->inclusions ?? [],
            'exclusions' => $this->exclusions ?? [],
            'duration_days' => (int) ($this->duration_days ?? 0),
            'duration_nights' => (int) ($this->duration_nights ?? 0),
            'price_per_person' => $this->price_per_person === null ? null : (float) $this->price_per_person,
            'discounted_price' => method_exists($this->resource, 'getDiscountedPrice') ? $this->getDiscountedPrice() : null,
            'child_price' => $this->child_price === null ? null : (float) $this->child_price,
            'start_location' => $this->start_location,
            'end_location' => $this->end_location,
            'region' => $this->region,
            'difficulty' => $this->difficulty,
            'cover_image' => $this->cover_image,
            'gallery' => $this->gallery ?? [],
            'available_from' => optional($this->available_from)->toDateString(),
            'available_to' => optional($this->available_to)->toDateString(),
            'available_seats' => $nextSchedule && method_exists($nextSchedule, 'getAvailableSeats')
                ? $nextSchedule->getAvailableSeats()
                : null,
            'itineraries' => ItineraryStopResource::collection($this->whenLoaded('itineraries')),
            'schedules' => TourScheduleResource::collection(
                $this->relationLoaded('schedules') ? $this->schedules : collect([$nextSchedule])->filter()->values()
            ),
            'related_places' => $this->relatedPlaces
                ? PlaceSummaryResource::collection($this->relatedPlaces)
                : [],
        ];
    }
}
