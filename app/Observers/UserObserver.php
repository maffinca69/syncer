<?php

namespace App\Observers;

use App\Jobs\CheckNewFavoriteTracksJob;
use App\Models\User;
use App\Services\PlaylistService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    private $playlistService;

    public function __construct(PlaylistService $playlistService)
    {
        $this->playlistService = $playlistService;
    }

    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
       $playlistId = $this->playlistService->createNewPublicPlaylist($user);

        if ($playlistId) {
           $user->update(['playlist_id' => $playlistId]);
           dispatch(new CheckNewFavoriteTracksJob($user->refresh_token));
       }
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        //
    }

    /**
     * Handle the User "forceDeleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        //
    }
}
