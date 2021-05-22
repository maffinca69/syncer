<?php

namespace App\Models;

use App\Services\PlaylistService;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;

/**
 * Class User
 * @package App\Models
 *
 * @property string refresh_token
 * @property string playlist_id
 * @property string last_song_id
 * @property Song[] songs
 * @property array info
 */
class User extends Model
{
    protected $casts = [
        'info' => 'array'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'refresh_token', 'info', 'playlist_id', 'last_song_id', 'spotify_id'
    ];

    public function songs()
    {
        return $this->hasMany(Song::class);
    }

    /**
     * Return array uris
     *
     * @return array
     */
    public function getSongsUris(): array
    {
        return $this->getSongsUrisBuilder()->toArray();
    }

    private function getSongsUrisBuilder()
    {
        return $this->songs->reverse()->map(function (Song $song) {
            return $song->uri;
        });
    }

    /**
     * Return array uris
     *
     * @return array
     */
    public function getSongsUrisWithLimit(): array
    {
        return $this->getSongsUrisBuilder()->take(PlaylistService::LIMIT_GET_SAVED_SONGS)->toArray();
    }
}
