<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $tenantOwnershipRelationshipName = 'stores';

    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'Colaborador';

    protected static ?string $pluralModelLabel = 'Colaboradores';

    protected static ?string $navigationLabel = 'Colaboradores';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationGroup = 'CONFIGURAÇÕES';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(7)
            ->schema([

                Forms\Components\Group::make()
                    ->columnSpan(5)
                    ->schema([

                        Forms\Components\Section::make()
                            ->columns(2)
                            ->schema([

                                Forms\Components\TextInput::make('name')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('email')
                                    ->label('E-mail')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('password')
                                    ->label('Senha')
                                    ->password()
                                    ->required()
                                    ->maxLength(255)
                                    ->revealable()
                                    ->dehydrateStateUsing(fn (string $state): string => Hash::make($state))
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->required(fn (string $operation): bool => $operation === 'create'),

                            ]),

                    ]),

                Forms\Components\Group::make()
                    ->columnSpan(2)
                    ->schema([

                        Forms\Components\Section::make()
                            ->schema([

                                Forms\Components\Toggle::make('is_active')
                                    ->label('Ativo?')
                                    ->required(),

                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Usuário')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Ativo?')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_owner')
                    ->label('Proprietário?')
                    ->alignCenter()
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
