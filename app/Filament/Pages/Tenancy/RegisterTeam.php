<?php

declare(strict_types = 1);

namespace App\Filament\Pages\Tenancy;

use App\Models\Platform;
use App\Models\Store;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Pages\Tenancy\RegisterTenant;
use Illuminate\Support\Str;

class RegisterTeam extends RegisterTenant
{
    public static function getLabel(): string
    {
        return 'Criar nova loja';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Loja')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                TextInput::make('slug')
                    ->label('Identificador da loja')
                    ->unique('stores', 'slug')
                    ->validationMessages([
                        'unique' => 'O :attribute já está em uso na plataforma.',
                    ])
                    ->readOnly()
                    ->live(onBlur: true),

                Select::make('platform_id')
                    ->label('Plataforma de e-commerce')
                    ->options(Platform::all()->pluck('name', 'id')),
            ]);
    }

    protected function handleRegistration(array $data): Store
    {
        $store = Store::create($data);

        $store->integrations()->create([
            'platform_id' => $data['platform_id'],
            'webhook' => Str::uuid()->toString(),
        ]);

        $store->users()->attach(auth()->guard('web')->user());

        return $store;
    }
}
