<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\Customer;
use App\Services\Waha\WahaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckExistsPhoneJob implements ShouldQueue
{
    use Queueable;

    public function __construct(protected WahaService $wahaService, protected Customer $customer)
    {
        //
    }

    public function handle(): void
    {
        $response = $this->wahaService->checkExists($this->customer->whatsapp);

        if ($response['numberExists'] === false) {
            return;
        }

        $this->customer->update([
            'whatsapp' => preg_replace('/[^0-9]/', '', $response['chatId']),
        ]);
    }
}
