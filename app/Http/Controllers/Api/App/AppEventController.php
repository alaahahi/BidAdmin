<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\AppEventsRequest;
use App\Services\EventService;
use Illuminate\Support\Str;

class AppEventController extends Controller
{
    public function __construct(private EventService $events) {}

    public function store(AppEventsRequest $request)
    {
        $data = $request->validated();
        $app = $data['app']
            ?? $request->header('X-App')
            ?? trim(($data['platform'] ?? '').' '.($data['app_version'] ?? ''));
        $app = Str::limit($app !== '' ? $app : 'app', 40, '');

        $results = $this->events->ingestAppEvents(
            $request->user(),
            $data['events'],
            $request->ip(),
            $app,
        );

        $failed = collect($results)->where('ok', false)->count();

        return response()->json([
            'data' => $results,
            'accepted' => count($results) - $failed,
            'failed' => $failed,
        ], $failed && $failed === count($results) ? 422 : 201);
    }
}
