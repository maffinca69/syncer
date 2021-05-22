<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class Song
 * @package App\Models
 *
 * @property int id
 * @property string uri
 * @property int user_id
 *
 * @property User user
 */
class Song extends Model
{
    protected $fillable = [
        'uri', 'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
