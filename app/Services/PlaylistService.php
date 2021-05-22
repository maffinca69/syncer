<?php


namespace App\Services;


use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlaylistService
{
    const FAVORITE_PLAYLIST_NAME = 'Favorite songs';
    const FAVORITE_PLAYLIST_DESCRIPTION = 'My favorite songs';
    const LIMIT_GET_SAVED_SONGS = 50;

    public function createNewPublicPlaylist(User $user): string
    {
        $accessToken = AuthService::getAccessToken($user->refresh_token);
        Log::info($accessToken);
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
     * @param null $next
     * @return array
     */
    public function getFavoritesSongs(User $user, $next = null): array
    {
        $accessToken = AuthService::getAccessToken($user->refresh_token);
        $baseUrl = 'https://api.spotify.com/v1/me/tracks?limit=' . self::LIMIT_GET_SAVED_SONGS;
        $response = Http::withToken($accessToken)->get($next ?? $baseUrl);
        $responseArray = $response->json();

        $list = $this->prepareListFavoriteSongs($responseArray);
        return [$list, $responseArray['next']];
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

    /**
     * @param User $user
     * @param array $songsUris
     * @param string $playlistId
     * @param int $position
     * @return array
     */
    public function addSongsToPlaylist(User $user, array $songsUris, string $playlistId, $position = 0): array
    {
        $accessToken = AuthService::getAccessToken($user->refresh_token);
        $response = Http::withToken($accessToken)->post(sprintf('https://api.spotify.com/v1/playlists/%s/tracks', $playlistId), [
            'uris' => $songsUris,
            'position' => $position, // to top
        ]);

        return $response->json();
    }

    public function removeSongsFromPlaylist(User $user, array $songsUris, string $playlistId): array
    {
        $accessToken = AuthService::getAccessToken($user->refresh_token);
        $response = Http::withToken($accessToken)->delete(sprintf('https://api.spotify.com/v1/playlists/%s/tracks', $playlistId), [
            'tracks' => $this->prepareUriSongsForDelete($songsUris),
        ]);

        return $response->json();
    }

    /**
     * Formatted array
     *
     * @param array $songs
     * @return array
     */
    private function prepareUriSongsForDelete(array $songs): array
    {
        return array_map(function ($uri) {
            return ['uri' => $uri];
        }, $songs);
    }

    /**
     * @param User $user
     * @param null $next
     * @param bool $syncAll
     * @return array
     */
    public function checkTracks(User $user, $next = null, $syncAll = false): array
    {
        [$songs, $next] = $this->getFavoritesSongs($user, $next);
        $favoritesTracks = $user->getSongsUrisWithLimit();
        Log::info('api');
        Log::info($songs);
        Log::info('database');
        Log::info($favoritesTracks);

        $list = $this->diffTracks($songs, $favoritesTracks, $syncAll);

        return [$list, $next];
    }

    /**
     * Return array of added tracks
     *
     * @param array $savedTracks - saved users tracks
     * @param array $favoritesTracks - tracks in public favorites playlist
     * @param bool $syncAll
     * @return array
     */
    private function diffTracks(array $savedTracks, array $favoritesTracks, $syncAll = false): array
    {
        return array_values(array_diff($savedTracks, $favoritesTracks, ));
    }
}
