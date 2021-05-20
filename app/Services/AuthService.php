<?php


namespace App\Services;


use App\Jobs\RefreshTokenJob;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthService
{
    const SPOTIFY_AUTH_BASE_URL = 'https://accounts.spotify.com/authorize';

    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public static function buildAuthUrl(): string
    {
        $params = http_build_query([
            'client_id' => config('spotify.client_id'),
            'response_type' => 'code',
            'redirect_uri' => route('callback'),
            'scope' => 'user-read-private%20user-read-email%20user-library-read%20playlist-read-private%20playlist-modify-public%20playlist-modify-private',
        ]);

        return sprintf('%s?%s', self::SPOTIFY_AUTH_BASE_URL, $params);
    }

    public function fetchToken(string $code)
    {
        $response = Http::asForm()->withBasicAuth(config('spotify.client_id'), config('spotify.client_secret'))->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => route('callback')
        ]);

        $resData = $response->json();

        $accessToken = $resData['access_token'];
        $refreshToken = $resData['refresh_token'];
        if ($this->saveAccessToken($accessToken, $refreshToken)) {
            Log::info('refresh - ' . $refreshToken);
            Log::info('access - ' . $accessToken);
            $this->sendToQueue($refreshToken, $resData['expires_in']);
            return [$refreshToken, $accessToken];
        }

        return false;
    }

    /**
     * @param string $refreshToken
     * @param int $expires_in
     */
    private function sendToQueue(string $refreshToken, int $expires_in)
    {
        dispatch((new RefreshTokenJob($refreshToken))->delay($expires_in));
    }

    private function saveAccessToken(string $refresh, string $access): bool
    {
        if (Cache::has($access)) {
            Cache::forget($access);
        }

        return Cache::put($access, $refresh);
    }

    /**
     * @param string $refreshToken
     * @return mixed
     */
    public static function getAccessToken(string $refreshToken)
    {
        return Cache::get($refreshToken, '');
    }

    public function refreshToken(string $token)
    {
        $response = Http::asForm()->withBasicAuth(config('spotify.client_id'), config('spotify.client_secret'))->post('https://accounts.spotify.com/api/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $token,
        ]);

        $resData = $response->json();

        if ($this->saveAccessToken($token, $resData['access_token'])) {
            $this->sendToQueue($token, $resData['expires_in']);
        }
    }
}
