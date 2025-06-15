<?php

declare(strict_types = 1);

namespace App\Filament\Pages\Tenancy;

use App\Enums\IntegrationTypeEnum;
use App\Models\Platform;
use App\Models\Store;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\EditTenantProfile;
use Illuminate\Database\Eloquent\Model;

class EditTeamProfile extends EditTenantProfile
{
    public static function getLabel(): string
    {
        return 'Editar loja';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                        ->label('Loja')
                        ->required()
                        ->maxLength(50),

                        Select::make('platform_id')
                            ->relationship('integrations', 'platform')
                            ->label('Plataforma de e-commerce')
                            ->options(Platform::where('type', IntegrationTypeEnum::ECOMMERCE)->pluck('name', 'id')),
                    ]),
            ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Store
    {
        $record->update([
            'name' => $data['name'],
        ]);

        $record->integrations()->update([
            'platform_id' => $data['platform_id'],
        ]);

        return $record;
    }
}
