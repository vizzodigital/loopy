<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Enums\IntegrationTypeEnum;
use App\Models\ConversationMessage;
use App\Models\Integration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class TranscribeAudioJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ConversationMessage $conversationMessage
    ) {
    }

    public function handle(): void
    {
        try {
            $integration = Integration::where('store_id', $this->conversationMessage->store_id)
                ->where('type', 'ai')
                ->first();

            if (!$integration || empty($integration->configs['api_key'])) {
                Log::error('OpenAI Integration not found or missing API key', [
                    'store_id' => $this->conversationMessage->store_id,
                ]);

                return;
            }

            $audioUrl = $this->conversationMessage->data['audio_url'] ?? null;

            if (!$audioUrl) {
                Log::error('Audio URL not found for conversation message', [
                    'conversation_message_id' => $this->conversationMessage->id,
                ]);

                return;
            }

            $tempPath = tempnam(sys_get_temp_dir(), 'audio') . '.ogg';
            file_put_contents($tempPath, file_get_contents($audioUrl));

            $client = OpenAI::client($integration->configs['api_key']);

            $result = $client->audio()->transcribe([
                'file' => fopen($tempPath, 'r'),
                'model' => 'whisper-1',
                'response_format' => 'json',
                'language' => 'pt',
            ]);

            unlink($tempPath);

            $transcription = $result['text'] ?? null;

            if ($transcription) {
                $this->conversationMessage->update([
                    'content' => $transcription,
                ]);

                Log::info('Audio transcribed successfully', [
                    'conversation_message_id' => $this->conversationMessage->id,
                    'transcription' => $transcription,
                ]);

                $ia = $this->conversationMessage->store->integrations()
                    ->where('type', IntegrationTypeEnum::AI)
                    ->where('is_active', true)
                    ->first();

                $agent = $this->conversationMessage->store->activeAgent();

                $whatsapp = $this->conversationMessage->store->integrations()
                    ->where('type', IntegrationTypeEnum::WHATSAPP)
                    ->first();

                SendSequenceAbandonedCartMessageJob::dispatch(
                    $this->conversationMessage->conversation_id,
                    $ia,
                    $whatsapp,
                    $agent
                );
            }
        } catch (\Throwable $e) {
            Log::error('Audio transcription failed', [
                'conversation_message_id' => $this->conversationMessage->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
