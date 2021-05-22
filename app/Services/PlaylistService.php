<?php


namespace App\Services;


use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlaylistService
{
    const FAVORITE_PLAYLIST_NAME = 'Favorite songs';
    const FAVORITE_PLAYLIST_DESCRIPTION = 'My favorite songs';

    public function createNewPublicPlaylist(User $user): string
    {
        $accessToken = AuthService::getAccessToken($user->refresh_token);
        $userId = $user->info['id'];

        $response = Http::withToken($accessToken)->post(sprintf('https://api.spotify.com/v1/users/%s/playlists', $userId), [
            'name' => self::FAVORITE_PLAYLIST_NAME,
            'description' => self::FAVORITE_PLAYLIST_DESCRIPTION,
            'public' => true
        ]);

        $data = $response->json();
        return $data['id'] ?? '';
    }

    /**
     * @param User $user
     * @return array
     */
    public function getFavoritesSongs(User $user): array
    {
        $accessToken = AuthService::getAccessToken($user->refresh_token);
        $response = Http::withToken($accessToken)->get('https://api.spotify.com/v1/me/tracks');

        return $this->prepareListFavoriteSongs($response->json());
    }

    /**
     * @param array $response
     * @return array
     */
    private function prepareListFavoriteSongs(array $response): array
    {
        $songsUris = [];
        foreach ($response['items'] as $song) {
            array_push($songsUris, $song['track']['uri']);
        }

        return $songsUris;
    }

    public function addSongsToPlaylist(User $user, array $songsUris, string $playlistId): array
    {
        $accessToken = AuthService::getAccessToken($user->refresh_token);
        $response = Http::withToken($accessToken)->post(sprintf('https://api.spotify.com/v1/playlists/%s/tracks', $playlistId), [
            'uris' => $songsUris,
            'position' => 0, // to top
        ]);

        return $response->json();
    }

    /**
     * Returned list uris new track
     * Returned empty array if haven't new tracks
     *
     * @param User $user
     * @return array
     */
    public function checkHasNewTrack(User $user): array
    {
        $songs = $this->getFavoritesSongs($user);
        $newSongs = [];

        foreach ($songs as $songUri) {
            if ($songUri == $user->last_song_id) {
                break;
            }

            array_push($newSongs, $songUri);
        }

        Log::info('count new songs - ' . count($newSongs));

        $user->update(['last_song_id' => current($songs)]);

        return $newSongs;
    }
}
