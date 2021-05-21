<?php


namespace App\Console\Commands;


use App\Jobs\CheckNewFavoriteTracksJob;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Console\Command;
use function Symfony\Component\String\s;

class RefreshUserTokensCommand  extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update refresh token for all user';

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
     * @param AuthService $service
     * @return mixed
     */
    public function handle(AuthService $service)
    {
        $this->info('Начинаю проверку');
        $users = User::all();
        foreach ($users as $user) {
            $token = $user->refresh_token;
            $service->refreshToken($token);
        }

        $this->info('Токены обновлены');
        return true;
    }
}
