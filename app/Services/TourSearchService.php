<?php

namespace App\Services;

use App\Models\Tour;
use App\Models\TourSchedule;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder;

class TourSearchService
{
    /**
     * Departure filtering closure based on search criteria.
     */
    public function departureScope(array $filters): Closure
    {
        return function ($query) use ($filters) {
            $query->bookable((int) ($filters['guests'] ?? 1));
            if (! empty($filters['date'])) {
                $query->whereDate('departure_date', $filters['date']);
            } elseif (! empty($filters['month'])) {
                $start = Carbon::createFromFormat('!Y-m', $filters['month']);
                $query->where('departure_date', '>=', $start)->where('departure_date', '<', $start->copy()->addMonth());
            }
        };
    }

    /**
     * Build the query for tour search with instant-based departure ordering.
     */
    public function query(array $filters): Builder
    {
        $departureFilter = $this->departureScope($filters);

        $nextSubquery = TourSchedule::query()
            ->selectRaw('MIN(COALESCE(departure_at, departure_date))')
            ->whereColumn('tour_id', 'tours.id');
        $departureFilter($nextSubquery);

        $query = Tour::query()
            ->select('tours.*')
            ->selectSub($nextSubquery, 'next_departure_at')
            ->bookableForGuests((int) ($filters['guests'] ?? 1))
            ->whereHas('schedules', $departureFilter)
            ->with([
                'category',
                'schedules' => function ($q) use ($departureFilter) {
                    $departureFilter($q);
                    $q->orderByRaw('COALESCE(departure_at, departure_date) ASC')->orderBy('id', 'ASC');
                },
            ]);

        if ($search = trim($filters['q'] ?? '')) {
            $query->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('region', 'like', '%'.$search.'%')->orWhere('start_location', 'like', '%'.$search.'%'));
        }
        if (! empty($filters['category']) && $filters['category'] !== 'All') {
            $query->whereHas('category', fn ($q) => $q->where('name', $filters['category']));
        }
        if (! empty($filters['travel_style']) && $filters['travel_style'] !== 'all') {
            $query->where('travel_style', $filters['travel_style']);
        }

        return $query->orderBy('next_departure_at', 'ASC')->orderBy('tours.id', 'ASC');
    }

    /**
     * Opt-in keyset cursor pagination over deterministic departure/tour ordering.
     *
     * @return array{items: \Illuminate\Database\Eloquent\Collection, has_more: bool, next_cursor: ?string, per_page: int}
     */
    public function paginateCursor(array $filters, int $perPage = 20, ?string $cursor = null): array
    {
        $departureFilter = $this->departureScope($filters);
        $nextSubquery = TourSchedule::query()
            ->selectRaw('MIN(COALESCE(departure_at, departure_date))')
            ->whereColumn('tour_id', 'tours.id');
        $departureFilter($nextSubquery);

        $query = $this->query($filters);

        if (! empty($cursor)) {
            $decoded = $this->decodeCursor($cursor);
            if ($decoded) {
                $cursorD = (string) $decoded['d'];
                $cursorId = (int) $decoded['id'];

                $subSql = '('.$nextSubquery->toSql().')';
                $bindings = $nextSubquery->getBindings();

                $query->where(function ($w) use ($subSql, $bindings, $cursorD, $cursorId) {
                    $w->whereRaw("{$subSql} > ?", array_merge($bindings, [$cursorD]))
                        ->orWhere(function ($w2) use ($subSql, $bindings, $cursorD, $cursorId) {
                            $w2->whereRaw("{$subSql} = ?", array_merge($bindings, [$cursorD]))
                                ->where('tours.id', '>', $cursorId);
                        });
                });
            }
        }

        // Fetch one extra item to accurately detect if more pages exist without a separate count query
        $results = $query->take($perPage + 1)->get();

        $hasMore = $results->count() > $perPage;
        $items = $hasMore ? $results->slice(0, $perPage)->values() : $results->values();

        $nextCursor = null;
        if ($hasMore && $items->isNotEmpty()) {
            $lastItem = $items->last();
            $nextCursor = $this->encodeCursor((string) $lastItem->next_departure_at, (int) $lastItem->id);
        }

        return [
            'items' => $items,
            'has_more' => $hasMore,
            'next_cursor' => $nextCursor,
            'per_page' => $perPage,
        ];
    }

    /**
     * Encode a cursor based on the instant departure and tour ID.
     */
    public function encodeCursor(string $departureAt, int $id): string
    {
        return rtrim(strtr(base64_encode(json_encode(['d' => $departureAt, 'id' => $id])), '+/', '-_'), '=');
    }

    /**
     * Decode a cursor string into its components.
     *
     * @return array{d: string, id: int}|null
     */
    public function decodeCursor(string $cursor): ?array
    {
        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
        if (! $decoded) {
            return null;
        }
        $data = json_decode($decoded, true);
        if (! is_array($data) || ! isset($data['d'], $data['id'])) {
            return null;
        }

        return [
            'd' => (string) $data['d'],
            'id' => (int) $data['id'],
        ];
    }
}
