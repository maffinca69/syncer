<?php


namespace App\Http\Controllers;


use App\Services\AuthService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthorizeController extends \Laravel\Lumen\Routing\Controller
{

    public function index()
    {
        return response()->json(['url' => AuthService::buildAuthUrl()]);
    }

    public function callback(Request $request, AuthService $service, UserService $userService)
    {
        [$refreshToken, $accessToken] = $service->fetchToken($request->get('code', ''));
        $userInfo = $userService->getMe($accessToken);
        $isSaveUserInfo = false;
        if ($userInfo) {
            Log::info('token sae - ' . $refreshToken);
            $user = $userService->saveUserInfo($refreshToken, $userInfo);
            $isSaveUserInfo = $user->exists;
        }

        return view('login', ['success' => $refreshToken && $isSaveUserInfo]);
    }

}
