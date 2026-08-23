<?php

namespace App\Http\Resources\CustomerApp;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class TourResource extends JsonResource
{
    protected ?Collection $relatedPlaces = null;

    public function withRelatedPlaces(?Collection $relatedPlaces): static
    {
        $this->relatedPlaces = $relatedPlaces;

        return $this;
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
            'cancellation_policy' => $this->cancellation_policy,
            'faqs' => $this->faqs ?? [],
            'duration_days' => (int) ($this->duration_days ?? 0),
            'duration_nights' => (int) ($this->duration_nights ?? 0),
            'min_group_size' => (int) ($this->min_group_size ?? 1),
            'max_group_size' => (int) ($this->max_group_size ?? 1),
            'price_per_person' => $this->price_per_person === null ? null : (float) $this->price_per_person,
            'discounted_price' => method_exists($this->resource, 'getDiscountedPrice') ? $this->getDiscountedPrice() : null,
            'child_price' => $this->child_price === null ? null : (float) $this->child_price,
            'start_location' => $this->start_location,
            'end_location' => $this->end_location,
            'region' => $this->region,
            'difficulty' => $this->difficulty,
            'cover_image' => MediaUrl::resolve($this->cover_image),
            'gallery' => collect($this->gallery ?? [])
                ->map(fn ($image) => MediaUrl::resolve($image))
                ->filter()
                ->values()
                ->all(),
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
