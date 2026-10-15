<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Venues\Actions\CreateEventAction;
use App\Domain\Venues\Actions\UpdateEventAction;
use App\Domain\Venues\Models\Event;
use App\Http\Controllers\Controller;
use App\Http\Requests\Venues\StoreEventRequest;
use App\Http\Resources\EventResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Event::query()->with(['venue', 'organizer']);

        if ($request->filled('venue_id')) {
            $query->where('venue_id', $request->input('venue_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->whereIn('status', ['published', 'live']);
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        $events = $query->orderBy('start_time', 'asc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => EventResource::collection($events),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function show(Event $event): JsonResponse
    {
        $event->loadMissing(['venue', 'organizer', 'tournament']);

        return response()->json([
            'success' => true,
            'data' => new EventResource($event),
        ]);
    }

    public function store(StoreEventRequest $request, CreateEventAction $action): JsonResponse
    {
        $event = $action->execute($request->user(), $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event scheduled successfully.',
            'data' => new EventResource($event),
        ], 201);
    }

    public function update(StoreEventRequest $request, Event $event, UpdateEventAction $action): JsonResponse
    {
        $updated = $action->execute($request->user(), $event, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'data' => new EventResource($updated),
        ]);
    }
}
