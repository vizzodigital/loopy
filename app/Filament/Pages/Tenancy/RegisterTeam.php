<?php

declare(strict_types = 1);

namespace App\Filament\Pages\Tenancy;

use App\Models\Store;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant;

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
                    ->label('Loja'),
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
