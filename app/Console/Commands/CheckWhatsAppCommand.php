<?php

declare(strict_types = 1);

namespace App\Console\Commands;

use App\Services\Waha\WahaService;
use Illuminate\Console\Command;

class CheckWhatsAppCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    public function __construct(protected WahaService $service)
    {
        parent::__construct();
    }

    public function handle()
    {
        $phone = "5548988061915";
        $response = $this->service->checkExists($phone);
        // $response = $this->service->sendText($phone, 'Teste');
        dd($response);
    }
}
