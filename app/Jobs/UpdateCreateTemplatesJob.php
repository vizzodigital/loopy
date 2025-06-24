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

    public function __construct(
        protected Integration $integration
    ) {
        //
    }

    public function handle(): void
    {
        $whatsappService = new WhatsAppService($this->integration);

        try {
            DB::transaction(function () use ($whatsappService) {
                $after = null;

                do {
                    $response = $whatsappService->listTemplates($after);
                    $templates = $response['data'] ?? [];
                    $paging = $response['paging'] ?? null;

                    foreach ($templates as $templateData) {
                        $body = $this->extractBody($templateData['components']);
                        $examples = $this->extractExamples($body);
                        Template::updateOrCreate(
                            [
                                'integration_id' => $this->integration->id,
                                'waba_template_id' => $templateData['id'],
                            ],
                            [
                                'store_id' => $this->integration->store_id,
                                'name' => $templateData['name'],
                                'language' => $templateData['language'],
                                'category' => $templateData['category'],
                                'body' => $body,
                                'examples' => $examples,
                                'components' => $templateData['components'],
                                'status' => $templateData['status'],
                                'payload' => $templateData, // Armazena o payload completo
                                'rejection_reason' => $templateData['rejection_reason'] ?? null,
                            ]
                        );
                    }
                    $after = $paging['cursors']['after'] ?? null;
                } while ($after);
            });
            Log::info("Templates synchronized successfully for integration ID {$this->integration->id}");
        } catch (\Exception $e) {
            Log::error("Failed to sync templates for integration ID {$this->integration->id}: {$e->getMessage()}");
            Notification::make()
                ->title('Erro ao sincronizar templates')
                ->body('Ocorreu um erro ao sincronizar os templates. Verifique os logs para mais detalhes.')
                ->danger()
                ->send();
        }
    }

    protected function extractBody(array $components): ?string
    {
        foreach ($components as $component) {
            if ($component['type'] === 'BODY') {
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

        preg_match_all('/{{\s*(\w+)\s*}}/', $body, $matches);
        $variables = collect($matches[1])->unique()->values();

        if ($variables->isEmpty()) {
            return null;
        }

        // Para o MVP, podemos deixar examples vazio ou mockar valores padrão
        return $variables->map(fn ($var) => [
            'name' => $var,
            'example' => "example_{$var}", // Exemplo genérico, ajustar conforme necessário
        ])->toArray();
    }
}
