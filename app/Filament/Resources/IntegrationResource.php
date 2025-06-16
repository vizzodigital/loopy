<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\IntegrationResource\Pages;
use App\Models\Integration;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class IntegrationResource extends Resource
{
    protected static ?string $tenantOwnershipRelationshipName = 'store';

    protected static ?string $model = Integration::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $modelLabel = 'Integração';

    protected static ?string $pluralModelLabel = 'Integrações';

    protected static ?string $navigationLabel = 'Integrações';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'INTEGRAÇÕES';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Integração')
                    ->columns(2)
                    ->schema([

                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\Placeholder::make('nameStore')
                                    ->label('Loja')
                                    ->content(fn (Integration $record): string => $record->store->name),

                                Forms\Components\Placeholder::make('webhookURL')
                                    ->label('Webhook')
                                    ->visible(fn (Integration $record): bool => $record->platform_id === 1 || $record->platform_id === 2 || $record->platform_id === 3 || $record->platform_id === 4 || $record->platform_id === 7 || $record->platform_id === 8 || $record->platform_id === 9)
                                    ->content(function (Integration $record) {
                                        if ($record->platform_id === 1) {
                                            return config('infynia.website') . '/api/webhook/store/' . $record->webhook;
                                        }

                                        if ($record->platform_id === 2) {
                                            return config('infynia.website') . '/api/webhook/store/' . $record->webhook;
                                        }

                                        if ($record->platform_id === 3) {
                                            return config('infynia.website') . '/api/webhook/store/' . $record->webhook;
                                        }

                                        if ($record->platform_id === 4) {
                                            return config('infynia.website') . '/api/webhook/store/' . $record->webhook;
                                        }

                                        if ($record->platform_id === 7) {
                                            return config('infynia.website') . '/api/webhook/whatsapp/official/' . $record->webhook;
                                        }

                                        if ($record->platform_id === 8) {
                                            return config('infynia.website') . '/api/webhook/whatsapp/z-api/' . $record->webhook;
                                        }

                                        if ($record->platform_id === 9) {
                                            return config('infynia.website') . '/api/webhook/whatsapp/z-api/' . $record->webhook;
                                        }
                                    }),

                                Forms\Components\KeyValue::make('configs')
                                    ->keyLabel('Chave')
                                    ->valueLabel('Valor')
                                    ->addable(false)
                                    ->deletable(false)
                                    ->editableKeys(false)
                                    ->required(),
                            ]),

                        Forms\Components\Group::make()
                            ->schema([

                                Forms\Components\Placeholder::make('documentation')
                                ->label('Instruções de uso')
                                    ->content(function ($record): HtmlString {
                                        $platform = $record->platform;

                                        if ($platform->id === 1) {
                                            //Instruções para Yampi
                                        }

                                        if ($platform->id === 2) {
                                            //Instruções para Shopify
                                        }

                                        $content = '
                                                <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">' . $record->platform->name . '</h2>
                                                <ol class="max-w-md space-y-1 text-gray-500 list-disc list-inside dark:text-gray-400">
                                                    <li>
                                                        Acesse ...
                                                    </li>
                                                    <li>
                                                        Clique ...
                                                    </li>
                                                    <li>
                                                        Finalize ...
                                                    </li>
                                                </ol>
                                            ';

                                        return new HtmlString($content);
                                    }),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('store.name')
                    ->label('Loja')
                    ->badge()
                    ->color('primary')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('platform.name')
                    ->label('Plataforma')
                    ->badge()
                    ->color('danger')
                    ->sortable(),
                Tables\Columns\TextColumn::make('webhook')
                    ->label('Identificador'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo de Integração'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Ativa?')
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
                    // Tables\Actions\DeleteBulkAction::make(),
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
