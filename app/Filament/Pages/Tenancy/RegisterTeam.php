<?php

declare(strict_types = 1);

namespace App\Filament\Pages\Tenancy;

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
        return 'Register team';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Loja')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                Select::make('store_id')
                    ->options(Store::all()->pluck('name', 'id')),

                TextInput::make('slug')
                    ->unique('stores', 'slug'),
            ]);
    }

    protected function handleRegistration(array $data): Store
    {
        $team = Store::create($data);

        $team->members()->attach(auth()->guard('web')->user());

        return $team;
    }
}
