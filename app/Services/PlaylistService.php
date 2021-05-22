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

    const START_CHECKED_POSITION_INDEX = 0;
    const END_CHECKED_POSITION_INDEX = self::LIMIT_GET_SAVED_SONGS - 10;

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
        Log::info($response);

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
        Log::info($accessToken);
        $baseUrl = 'https://api.spotify.com/v1/me/tracks?limit=' . self::LIMIT_GET_SAVED_SONGS;
        Log::info($baseUrl);
        $response = Http::withToken($accessToken)
            ->get($next ?? $baseUrl);
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

        Log::info('remove');
        Log::info($response);

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
     * @return array
     */
    public function checkTracks(User $user, $next = null): array
    {
        [$songs, $next] = $this->getFavoritesSongs($user, $next);
        $favoritesTracks = $user->getSongsUris();

        $add = $this->diffAddedTracks($songs, $favoritesTracks);
        $remove = $this->diffDeletedTracks($songs, $favoritesTracks);

        Log::info('count add songs - ' . count($add));
        Log::info('count remove songs - ' . count($remove));

        return [$add, $remove, $next];
    }

    /**
     * Return array of added tracks
     *
     * @param array $savedTracks - saved users tracks
     * @param array $favoritesTracks - tracks in public favorites playlist
     * @return array
     */
    private function diffAddedTracks(array $savedTracks, array $favoritesTracks): array
    {
        return array_values(array_diff($savedTracks, $favoritesTracks));
    }

    /**
     * Return array of deleted tracks
     *
     * @param array $savedTracks - saved users tracks (api)
     * @param array $favoritesTracks - tracks in public favorites playlist (database)
     * @return array
     */
    private function diffDeletedTracks(array $savedTracks, array $favoritesTracks): array
    {
        return array_values(array_diff($favoritesTracks, $savedTracks));
    }
}
