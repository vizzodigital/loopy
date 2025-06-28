<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Enums\IntegrationTypeEnum;
use App\Models\Agent;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Integration;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TranscribeAudioMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ConversationMessage $message,
        protected Conversation $conversation,
        protected Integration $integration,
        protected Agent $agent
    ) {
        //
    }

    public function handle(): void
    {
        try {
            // 1. Extrair ID do áudio do payload
            $audioId = $this->message->payload['value']['messages'][0]['audio']['id'] ?? null;

            if (!$audioId) {
                $this->updateMessageWithError('ID do áudio não encontrado');

                return;
            }

            // 2. Obter URL do áudio via WhatsApp API
            $audioUrl = $this->getAudioUrl($audioId);

            if (!$audioUrl) {
                $this->updateMessageWithError('URL do áudio não pôde ser obtida');

                return;
            }

            // 3. Baixar o arquivo com headers de autenticação
            $audioContent = $this->downloadAudio($audioUrl);

            if (!$audioContent) {
                $this->updateMessageWithError('Falha ao baixar o áudio');

                return;
            }

            // 4. Salvar temporariamente
            $fileName = 'audios/' . Str::uuid() . '.ogg';
            Storage::disk('public')->put($fileName, $audioContent);
            $filePath = Storage::disk('public')->path($fileName);

            // 5. Transcrever
            $openAI = new OpenAIService($this->integration, $this->agent);
            $transcription = $openAI->transcribeAudio($filePath);

            // 6. Atualizar mensagem
            $this->message->update([
                'content' => $transcription ?? '[Áudio não pôde ser transcrito]',
            ]);

            // 7. Limpar arquivo temporário
            Storage::disk('public')->delete($fileName);

            // 8. Disparar próximo job apenas se transcrição foi bem-sucedida
            if ($transcription) {
                $this->dispatchNextJob();
            }
        } catch (\Exception $e) {
            Log::error('Erro na transcrição de áudio', [
                'message_id' => $this->message->id,
                'error' => $e->getMessage(),
            ]);

            $this->updateMessageWithError('Erro interno na transcrição');
        }
    }

    /**
     * Obter URL do áudio via WhatsApp Business API
     */
    private function getAudioUrl(string $audioId): ?string
    {
        try {
            $whatsappIntegration = $this->conversation->store->integrations()
                ->where('type', IntegrationTypeEnum::WHATSAPP)
                ->first();

            if (!$whatsappIntegration) {
                throw new \Exception('Integração WhatsApp não encontrada');
            }

            $accessToken = $whatsappIntegration->configs['access_token'] ?? null;

            if (!$accessToken) {
                throw new \Exception('Access token não configurado');
            }

            // Fazer requisição para obter dados do media
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->get("https://graph.facebook.com/v23.0/{$audioId}");

            if (!$response->successful()) {
                throw new \Exception('Falha ao obter dados do áudio: ' . $response->body());
            }

            $data = $response->json();

            return $data['url'] ?? null;
        } catch (\Exception $e) {
            Log::error('Erro ao obter URL do áudio', [
                'audio_id' => $audioId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Baixar áudio com autenticação
     */
    private function downloadAudio(string $audioUrl): ?string
    {
        try {
            $whatsappIntegration = $this->conversation->store->integrations()
                ->where('type', IntegrationTypeEnum::WHATSAPP)
                ->first();

            $accessToken = $whatsappIntegration->configs['access_token'] ?? null;

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])->timeout(30)->get($audioUrl);

            if (!$response->successful()) {
                throw new \Exception('Falha ao baixar áudio: ' . $response->status());
            }

            return $response->body();
        } catch (\Exception $e) {
            Log::error('Erro ao baixar áudio', [
                'url' => $audioUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Atualizar mensagem com erro
     */
    private function updateMessageWithError(string $error): void
    {
        $this->message->update([
            'content' => "[Erro: {$error}]",
        ]);
    }

    /**
     * Disparar próximo job
     */
    private function dispatchNextJob(): void
    {
        $aiIntegration = $this->conversation->store->integrations()
            ->where('type', IntegrationTypeEnum::AI)
            ->where('is_active', true)
            ->first();

        $whatsappIntegration = $this->conversation->store->integrations()
            ->where('type', IntegrationTypeEnum::WHATSAPP)
            ->first();

        $agent = $this->conversation->store->activeAgent();

        if ($aiIntegration && $whatsappIntegration && $agent) {
            SendSequenceAbandonedCartMessageJob::dispatch(
                $this->conversation,
                $aiIntegration,
                $whatsappIntegration,
                $agent
            );
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TranscribeAudioMessageJob failed', [
            'message_id' => $this->message->id,
            'conversation_id' => $this->conversation->id,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        $this->updateMessageWithError('Falha na transcrição');
    }
}
