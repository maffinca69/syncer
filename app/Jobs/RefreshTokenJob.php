<?php

namespace App\Jobs;

use App\Services\AuthService;
use GuzzleHttp\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RefreshTokenJob extends Job  implements ShouldQueue
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
     * @param AuthService $service
     * @return void
     */
    public function handle(AuthService $service)
    {
        $service->refreshToken($this->token);
    }
}
