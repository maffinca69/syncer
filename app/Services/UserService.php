<?php


namespace App\Services;


use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class UserService
{
    /**
     * Get info account
     * @param string $token
     * @return array|mixed
     */
    public function getMe(string $token): array
    {
        $response = Http::withToken($token)->get('https://api.spotify.com/v1/me');
        $data = $response->json();

        if (isset($data['error'])) {
            return [];
        }

        return $data;
    }

    /**
     * @param string $token
     * @param array $data
     * @return Model
     */
    public function saveUserInfo(string $token, array $data): Model
    {
        return User::query()->updateOrCreate(['spotify_id' => $data['id']], [
            'refresh_token' => $token,
            'info' => $data,
            'spotify_id' => $data['id']
        ]);
    }
}
