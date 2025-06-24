<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                    <span
                        class="@if ($record->status->value === 'active') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                        @elseif($record->status->value === 'closed') bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                        @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                        {{ $record->status->getLabel() }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Iniciada em</label>
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        {{ $record->started_at?->format('d/m/Y H:i') ?? 'N/A' }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Loja</label>
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        {{ $record->store->name }}
                    </p>
                </div>
            </div>
        </div>

        <x-filament::section>
            <div class="space-y-4">
                <div id="chat-container" class="h-96 overflow-y-auto rounded-lg bg-white p-4 dark:bg-gray-900"
                    wire:poll.5s="getMessagesProperty">

                    @forelse($this->messages as $message)
                        <div class="@if ($message->sender_type->value === 'ai' || $message->sender_type->value === 'human') justify-end @else justify-start @endif mb-4 flex">

                            <div
                                class="@if ($message->sender_type->value === 'ai' || $message->sender_type->value === 'human') ml-auto bg-gray-300 dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700
                                @else
                                    bg-gray-300 dark:bg-gray-800 text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700 mr-auto @endif max-w-lg rounded-lg px-4 py-2 lg:max-w-md">

                                <p class="whitespace-pre-wrap text-sm">{{ $message->content }}</p>

                                <div
                                    class="@if ($message->sender_type->value === 'ai' || $message->sender_type->value === 'human') text-blue-100
                                    @else
                                        text-gray-500 dark:text-gray-400 @endif mt-2 flex items-center justify-between text-xs">

                                    <span>{{ $message->sent_at->format('H:i') }}</span>

                                    @if ($message->sender_type->value === 'ai' || $message->sender_type->value === 'human')
                                        <span class="ml-2">
                                            @if ($message->was_read)
                                                ✓✓
                                            @else
                                                ✓
                                            @endif
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center text-gray-500 dark:text-gray-400">
                            <p>Nenhuma mensagem ainda</p>
                        </div>
                    @endforelse
                </div>

                <x-filament-panels::form>
                    {{ $this->form }}
                    <x-filament-panels::form.actions :actions="$this->getFormActions()" />
                </x-filament-panels::form>
            </div>
        </x-filament::section>
    </div>

    <script>
        function scrollToBottom() {
            const chatContainer = document.getElementById('chat-container');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            scrollToBottom();
        });

        document.addEventListener('livewire:updated', function() {
            scrollToBottom();
        });

        window.addEventListener('messagesUpdated', function() {
            setTimeout(scrollToBottom, 100);
        });
    </script>
</x-filament-panels::page>
