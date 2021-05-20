<?php


namespace App\Jobs;


use App\Models\User;
use App\Services\PlaylistService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckNewFavoriteTracksJob extends Job implements ShouldQueue
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
     * @return void
     */
    public function handle(PlaylistService $playlistService)
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return;
        }

        $newTracks = $playlistService->checkHasNewTrack($user);
        Log::info('Count new tracks - ' . count($newTracks));
        if ($newTracks) {
            $playlistService->addSongsToPlaylist($user, $newTracks, $user->playlist_id);
        }
    }

    private function getUser()
    {
        return User::query()->orderByDesc('id')->where('refresh_token', $this->token)->first();
    }
}
