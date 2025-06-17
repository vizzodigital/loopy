<?php

declare(strict_types = 1);

namespace App\Jobs;

use App\Models\Agent;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Models\Integration;
use App\Services\OpenAI\OpenAIService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TranscribeAudioMessageJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
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
        $audioUrl = $this->message->payload['audio']['audioUrl'] ?? null;

        if (!$audioUrl) {
            return;
        }

        $audioContent = file_get_contents($audioUrl);
        $fileName = 'audios/' . Str::uuid() . '.ogg';

        Storage::disk('public')->put($fileName, $audioContent);
        $filePath = Storage::disk('public')->path($fileName);

        $openAI = new OpenAIService($this->integration, $this->agent);

        $transcription = $openAI->transcribeAudio($filePath);

        $this->message->update([
            'content' => $transcription ?? '[Áudio não pôde ser transcrito]',
        ]);

        Storage::disk('public')->delete($fileName);

        dispatch(new SendSequenceAbandonedCartMessageJob(
            $this->conversation,
            $this->conversation->store->integrations()
                ->where('type', 'ai')
                ->first(),
            $this->conversation->store->integrations()
                ->where('type', 'whatsapp')
                ->first(),
            $this->conversation->store->activeAgent()
        ));
    }
}
