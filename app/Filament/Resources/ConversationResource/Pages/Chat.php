<?php

declare(strict_types = 1);

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Enums\ConversationSenderTypeEnum;
use App\Filament\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class Chat extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public Conversation $record;

    protected static string $resource = ConversationResource::class;

    protected static string $view = 'filament.resources.conversation-resource.pages.chat';

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Textarea::make('content')
                    ->label('Nova mensagem')
                    ->placeholder('Digite sua mensagem...')
                    ->rows(3)
                    ->required()
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function send(): void
    {
        $data = $this->form->getState();

        ConversationMessage::create([
            'store_id' => $this->record->store_id,
            'conversation_id' => $this->record->id,
            'sender_type' => ConversationSenderTypeEnum::HUMAN,
            'content' => $data['content'],
            'payload' => [],
            'was_read' => false,
            'sent_at' => now(),
        ]);

        $this->form->fill();

        Notification::make()
            ->title('Mensagem enviada!')
            ->success()
            ->send();

        $this->dispatch('messagesUpdated');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('send')
                ->label('Enviar')
                ->action('send'),
        ];
    }

    public function getMessagesProperty()
    {
        return $this->record->conversationMessages()
            ->orderBy('sent_at', 'asc')
            ->get();
    }

    public function markAsRead(): void
    {
        $this->record->conversationMessages()
            ->where('was_read', false)
            ->where('sender_type', '!=', ConversationSenderTypeEnum::HUMAN)
            ->update(['was_read' => true]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllAsRead')
                ->label('Marcar todas como lidas')
                ->icon('heroicon-o-check-circle')
                ->action('markAsRead'),
        ];
    }

    public function getTitle(): string
    {
        return "Conversa #{$this->record->id}";
    }
}
