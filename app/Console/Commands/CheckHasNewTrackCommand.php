<?php


namespace App\Console\Commands;


use App\Jobs\CheckNewFavoriteTracksJob;
use App\Models\User;
use Illuminate\Console\Command;

class CheckHasNewTrackCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:tracks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check have new track for adding to public playlist';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->info('Начинаю проверку');
        $users = User::all();
        foreach ($users as $user) {
            $this->info('Начало проверки плейлиста - ' . $user->playlist_id);
            dispatch(new CheckNewFavoriteTracksJob($user->refresh_token));
        }

        $this->info('Проверка закончена');
        return true;
    }
}
