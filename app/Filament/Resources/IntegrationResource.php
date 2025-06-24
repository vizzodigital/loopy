<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\IntegrationResource\Pages;
use App\Models\Integration;
use App\Models\Platform;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
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
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([

                        Forms\Components\Group::make()
                            ->schema([

                                Forms\Components\Fieldset::make('Dados da integração')
                                    ->columns(1)
                                    ->schema([

                                        Forms\Components\Placeholder::make('nameStore')
                                            ->label('Loja')
                                            ->content(fn (Integration $record): string => $record->store->name),

                                        Forms\Components\Select::make('platform_id')
                                            ->required()
                                            ->label('Plataforma')
                                            ->options(function (Integration $record) {
                                                return Platform::where('type', $record->type->value)->pluck('name', 'id');
                                            })
                                            ->live(onBlur: true),

                                        Forms\Components\Placeholder::make('webhookURL')
                                            ->label('Webhook')
                                            ->live()
                                            ->visible(fn (Integration $record): bool => $record->platform_id === 1 || $record->platform_id === 2 || $record->platform_id === 3 || $record->platform_id === 4 || $record->platform_id === 7 || $record->platform_id === 8 || $record->platform_id === 9)
                                            ->content(function (Get $get, Integration $record): HtmlString {
                                                if ($record->platform_id >= 1 && $record->platform_id <= 4) {
                                                    return new HtmlString('<span class="text-yellow-500">' . config('infynia.website') . '/api/webhook/store/' . $get('webhook') . '</span>');
                                                }

                                                if ($record->platform_id === 7) {
                                                    return new HtmlString('<span class="font-semibold">URL de Callback:</span><br> <span class="text-primary-500">' . config('infynia.website') . '/api/webhook/whatsapp/official/' . $get('webhook') . '</span><br><br><span class="font-semibold">Verificar token:</span><br> <span class="text-primary-500">' . $get('webhook') . '</span>');
                                                }

                                                if ($record->platform_id === 8) {
                                                    return new HtmlString('<span class="text-yellow-500">' . config('infynia.website') . '/api/webhook/whatsapp/z-api/' . $get('webhook') . '</span>');
                                                }

                                                if ($record->platform_id === 9) {
                                                    return new HtmlString('<span class="text-yellow-500">' . config('infynia.website') . '/api/webhook/whatsapp/waha/' . $get('webhook') . '</span>');
                                                }

                                                return new HtmlString('<span class="text-yellow-500">error</span>');
                                            }),

                                        Forms\Components\KeyValue::make('configs')
                                            ->label('Configurações')
                                            ->keyLabel('Chave')
                                            ->valueLabel('Valor')
                                            ->addable(false)
                                            ->deletable(false)
                                            ->editableKeys(false)
                                            ->required(),

                                    ]),
                            ]),

                        Forms\Components\Group::make()
                            ->schema([

                                Forms\Components\Fieldset::make('Instruções para implementação')
                                    ->columns(1)
                                    ->schema([

                                        Forms\Components\Placeholder::make('documentation')
                                            ->label('Instruções de uso')
                                            ->content(function ($record): HtmlString {
                                                $platform = $record->platform;

                                                if ($platform->id === 1) {
                                                    $steps = '';
                                                }

                                                if ($platform->id === 2) {
                                                    $steps = '';
                                                }

                                                if ($platform->id === 3) {
                                                    $steps = '';
                                                }

                                                if ($platform->id === 4) {
                                                    $steps = '';
                                                }

                                                if ($platform->id === 5) {
                                                    $steps = '';
                                                }

                                                if ($platform->id === 6) {
                                                    $steps = '';
                                                }

                                                if ($platform->id === 7) {
                                                    $steps = '
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                                            <ul class="list-disc pl-5">
                                                                <li>Acesse o App do WhatsApp Business.</li>
                                                                <li>Pegue os dados da sua conta.</li>
                                                                <li>Clique em Webhooks e escolha a opção "Select Product": Whatsapp Business Account.</li>
                                                                <li>Preencha os campos: URL de callback e Verificar token com os dados da sua conta Infynia.</li>
                                                            </ul>
                                                        </p>
                                                    ';
                                                }

                                                if ($platform->id === 8) {
                                                    $steps = '';
                                                }

                                                if ($platform->id === 9) {
                                                    $steps = '';
                                                }

                                                $content = '
                                                    <h2 class="mb-2 text-lg font-semibold text-gray-900 dark:text-white">' . $record->platform->name . '</h2>
                                                    ' . $steps . '
                                                ';

                                                return new HtmlString($content);
                                            }),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->paginated(false)
            ->searchable(false)
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

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime()
                    ->sortable(),
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
