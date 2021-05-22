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

    /**
     * Create a new job instance.
     *
     * @param string $token
     */
    public function __construct(string $token)
    {
        $this->token = $token;
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

        [$add, $remove, $nextUrl] = $playlistService->checkTracks($user, $next);

        $existsTrack = $user->getSongsUris();
        $add = array_diff($add, $existsTrack);
        $remove = array_intersect($existsTrack,$remove);

        Log::info('Count new tracks - ' . count($add));
        Log::info('Count remove tracks - ' . count($remove));

        if ($add) {
            $position = !$next ? 0 : count($add) - 1;
            $playlistService->addSongsToPlaylist($user, $add, $user->playlist_id, $position);
            $this->addSong($user, $add);
        }

        if ($remove && (!$next && !$nextUrl)) {
            $playlistService->removeSongsFromPlaylist($user, $remove, $user->playlist_id);
            $this->removeSong($remove);
        }

        if ($nextUrl) {
            $this->handle($playlistService, $nextUrl);
        }
    }

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
