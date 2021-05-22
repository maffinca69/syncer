<?php


namespace App\Jobs;


use App\Models\Song;
use App\Models\User;
use App\Services\PlaylistService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncJob extends Job implements ShouldQueue
{
    use SerializesModels;

    private $token;

    private $service;

    private $syncAll;

    /**
     * Create a new job instance.
     *
     * @param string $token
     * @param bool $syncAll
     */
    public function __construct(string $token, $syncAll = false)
    {
        $this->token = $token;
        $this->syncAll = $syncAll;
    }

    /**
     * Execute the job.
     *
     * @param PlaylistService $playlistService
     * @param null $next
     * @return void
     */
    public function handle(PlaylistService $playlistService, $next = null)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return;
        }

        [$list, $nextUrl] = $playlistService->checkTracks($user, $next, $this->syncAll);

        $add = [];
        $remove = [];
        Log::info($list);
        // todo: REFACTORING!!!
        if (!$this->syncAll) {
            // fuck fuck fuck
            foreach ($list as $uri) {
                $song = Song::query()->where('uri', $uri)->exists();
                if ($song) {
                    $this->removeSong([$uri]);
                    array_push($remove, $uri);
                } else {
                    Song::query()->create(['uri' => $uri, 'user_id' => $user->id]);
                    array_push($add, $uri);
                }
            }

            if ($add) {
                $playlistService->addSongsToPlaylist($user, $add, $user->playlist_id, 0);
            }

            if ($remove) {
                $playlistService->removeSongsFromPlaylist($user, $remove, $user->playlist_id);
            }
        } else {
            $add = $list;
            if ($add) {
                $position = !$next ? 0 : count($add) - 1;
                $this->addSong($user,$add);
                $playlistService->addSongsToPlaylist($user, $add, $user->playlist_id, $position);
            }

            if ($nextUrl) {
                $this->handle($playlistService, $nextUrl);
            }
        }
    }

    /**
     * @param array $songs
     * @return mixed
     */
    private function removeSong(array $songs)
    {
        return Song::query()->whereIn('uri', $songs)->delete();
    }

    private function addSong(User $user, array $songs)
    {
        foreach ($songs as $song) {
            Song::query()->firstOrCreate(['uri' => $song], [
                'uri' => $song,
                'user_id' => $user->id
            ]);
        }
    }

    private function getUser()
    {
        return User::query()->orderByDesc('id')->where('refresh_token', $this->token)->first();
    }
}
