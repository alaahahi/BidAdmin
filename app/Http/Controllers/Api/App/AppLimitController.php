<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AppLimitController extends Controller
{
    public function show(Request $request)
    {
        $customer = $request->user()->customer;
        abort_unless($customer, 403);

        $activeVehicles = $customer->activeVehicleCount();
        $carLimit = $customer->car_limit;

        return response()->json([
            'data' => [
                'bid_limit' => $customer->bid_limit,
                'car_limit' => $carLimit,
                'can_bid' => (bool) $customer->can_bid,
                'active_vehicles' => $activeVehicles,
                'vehicles_remaining' => $carLimit === null ? null : max(0, (int) $carLimit - $activeVehicles),
            ],
        ]);
    }
}
