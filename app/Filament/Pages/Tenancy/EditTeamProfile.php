<?php

declare(strict_types = 1);

namespace App\Filament\Pages\Tenancy;

use App\Models\Platform;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Pages\Tenancy\EditTenantProfile;
use Illuminate\Support\Str;

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
                TextInput::make('name')
                    ->label('Loja')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                Hidden::make('slug')
                    ->unique('stores', 'slug'),

                Select::make('platform_id')
                    ->options(Platform::all()->pluck('name', 'id')),
            ]);
    }
}
