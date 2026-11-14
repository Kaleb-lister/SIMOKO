<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEventsRequest;
use App\Models\Device;
use App\Services\IngestService;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{
    public function store(StoreEventsRequest $request, IngestService $ingestService): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $response = $ingestService->ingest(
            $device,
            $request->input('firmware'),
            $request->input('events'),
            $request->ip()
        );

        return response()->json($response, 200);
    }
}
