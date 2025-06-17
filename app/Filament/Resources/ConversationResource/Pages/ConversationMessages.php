<?php

declare(strict_types = 1);

namespace App\Filament\Resources\ConversationResource\Pages;

use App\Filament\Resources\ConversationResource;
use Filament\Resources\Pages\Page;

class ConversationMessages extends Page
{
    protected static string $resource = ConversationResource::class;

    protected static string $view = 'filament.resources.conversation-resource.pages.conversation-messages';
}
