<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Http\Requests\App\AppLoginRequest;
use App\Http\Resources\AppCustomerResource;
use App\Services\AppAuthService;

class AppAuthController extends Controller
{
    public function __construct(private AppAuthService $auth) {}

    public function login(AppLoginRequest $request)
    {
        $data = $request->validated();
        $result = $this->auth->login(
            $request,
            $data['code'],
            $data['device_name'],
            $data['platform'],
            $data['app_version'],
        );

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'customer' => (new AppCustomerResource($result['customer']))->resolve(),
        ]);
    }
}
