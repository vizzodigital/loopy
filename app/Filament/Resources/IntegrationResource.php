<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\IntegrationResource\Pages;
use App\Models\Integration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Table;

class IntegrationResource extends Resource
{
    protected static ?string $model = Integration::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('storeName')
                    ->label('Loja')
                    ->content(fn (Integration $record): string => $record->name),

                Forms\Components\Placeholder::make('platformName')
                    ->label('Plataforma')
                    ->content(fn (Integration $record): string => $record->platform->name),

                Forms\Components\Placeholder::make('webhookURL')
                    ->label('Webhook')
                    ->content(fn (Integration $record): string => env('APP_URL') . '/api/webhook/' . $record->webhook),

                Forms\Components\TextInput::make('secret')
                    ->maxLength(255)
                    ->default(null),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Loja')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('platform.name')
                    ->label('Plataforma')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('webhook')
                    ->label('URL do Webhook')
                    ->prefix(env('APP_URL') . "/api/webhook/")
                    ->icon('heroicon-o-document-duplicate')
                    ->iconPosition(IconPosition::After)
                    ->copyable()
                    ->copyMessage('Webhook copiado com sucesso!')
                    ->copyableState(fn (string $state): string => env('APP_URL') . "/api/webhook/{$state}")
                    ->copyMessageDuration(1500),

                Tables\Columns\TextColumn::make('secret')
                    ->label('Chave de Segurança')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('first_webhook_at')
                    ->label('Primeiro Webhook')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('last_webhook_at')
                    ->label('Último Webhook')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListIntegrations::route('/'),
            'create' => Pages\CreateIntegration::route('/create'),
            'edit' => Pages\EditIntegration::route('/{record}/edit'),
        ];
    }
}
