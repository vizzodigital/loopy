<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Template;
use App\Services\Meta\WhatsAppService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateCreateTemplatesJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // Aumentado para 5 minutos

    /**
     * Maximum number of templates to process per batch
     *
     * @var int
     */
    protected int $batchSize = 50;

    /**
     * Maximum number of API calls to prevent infinite loops
     *
     * @var int
     */
    protected int $maxApiCalls = 20;

    public function __construct(
        protected Integration $integration
    ) {
        //
    }

    public function handle(): void
    {
        $whatsappService = new WhatsAppService($this->integration);

        try {
            $this->syncTemplates($whatsappService);
            Log::info("Templates synchronized successfully for integration ID {$this->integration->id}");
        } catch (\Exception $e) {
            Log::error("Failed to sync templates for integration ID {$this->integration->id}: {$e->getMessage()}");
            $this->sendErrorNotification();

            throw $e;
        }
    }

    protected function syncTemplates(WhatsAppService $whatsappService): void
    {
        $after = null;
        $apiCallCount = 0;
        $templatesBatch = [];

        do {
            if (++$apiCallCount > $this->maxApiCalls) {
                Log::warning("Maximum API calls reached for integration ID {$this->integration->id}");

                break;
            }

            $response = $whatsappService->listTemplates($after);

            if (!isset($response['data']) || !is_array($response['data'])) {
                Log::warning("Invalid response format from WhatsApp API for integration ID {$this->integration->id}");

                break;
            }

            $templates = $response['data'];
            $paging = $response['paging'] ?? null;

            foreach ($templates as $templateData) {
                if (!$this->isValidTemplateData($templateData)) {
                    Log::warning("Invalid template data received", ['template' => $templateData]);

                    continue;
                }

                $templatesBatch[] = $this->prepareTemplateData($templateData);

                if (count($templatesBatch) >= $this->batchSize) {
                    $this->processBatch($templatesBatch);
                    $templatesBatch = [];
                }
            }

            $after = $paging['cursors']['after'] ?? null;
        } while ($after);

        if (!empty($templatesBatch)) {
            $this->processBatch($templatesBatch);
        }
    }

    protected function processBatch(array $templatesBatch): void
    {
        DB::transaction(function () use ($templatesBatch) {
            foreach ($templatesBatch as $templateData) {
                Template::updateOrCreate(
                    [
                        'integration_id' => $this->integration->id,
                        'waba_template_id' => $templateData['waba_template_id'],
                    ],
                    $templateData
                );
            }
        });

        Log::debug("Processed batch of " . count($templatesBatch) . " templates for integration ID {$this->integration->id}");
    }

    protected function prepareTemplateData(array $templateData): array
    {
        $body = $this->extractBody($templateData['components'] ?? []);
        $examples = $this->extractExamples($body);

        return [
            'waba_template_id' => $templateData['id'],
            'store_id' => $this->integration->store_id,
            'name' => $templateData['name'] ?? '',
            'language' => $templateData['language'] ?? '',
            'category' => $templateData['category'] ?? '',
            'body' => $body,
            'examples' => $examples,
            'components' => $templateData['components'] ?? [],
            'status' => $templateData['status'] ?? '',
            'payload' => $templateData,
            'rejection_reason' => $templateData['rejection_reason'] ?? null,
        ];
    }

    protected function isValidTemplateData(array $templateData): bool
    {
        return isset($templateData['id']) &&
               isset($templateData['name']) &&
               isset($templateData['components']);
    }

    protected function extractBody(array $components): ?string
    {
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'BODY') {
                return $component['text'] ?? null;
            }
        }

        return null;
    }

    protected function extractExamples(?string $body): ?array
    {
        if (!$body) {
            return null;
        }

        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $body, $matches);
        $variables = collect($matches[1])->unique()->values();

        if ($variables->isEmpty()) {
            return null;
        }

        return $variables->map(fn ($var) => [
            'name' => $var,
            'example' => is_numeric($var) ? "example_value_{$var}" : "example_{$var}",
        ])->toArray();
    }

    protected function sendErrorNotification(): void
    {
        Notification::make()
            ->title('Erro ao sincronizar templates')
            ->body("Erro na sincronização dos templates da integração {$this->integration->name}.")
            ->danger()
            ->send();
    }
}
