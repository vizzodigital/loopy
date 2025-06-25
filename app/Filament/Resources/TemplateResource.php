<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Enums\IntegrationTypeEnum;
use App\Enums\TemplateCategoryEnum;
use App\Filament\Resources\TemplateResource\Pages;
use App\Models\Integration;
use App\Models\Template;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?string $modelLabel = 'WhatsApp Template';

    protected static ?string $pluralModelLabel = 'WhatsApp Templates';

    protected static ?string $navigationLabel = 'WhatsApp Templates';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'INTEGRAÇÕES';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                           ->columns(3)
                           ->schema([

                               Forms\Components\Hidden::make('store_id')
                                    ->required()
                                    ->default(Filament::getTenant()->id),

                               Forms\Components\Hidden::make('integration_id')
                                    ->required()
                                    ->default(function () {
                                        $storeId = Filament::getTenant()->id;
                                        $integration = Integration::where('store_id', $storeId)
                                            ->where('platform_id', 7)
                                            ->where('type', IntegrationTypeEnum::WHATSAPP)
                                            ->first();

                                        if (!$integration) {
                                            Notification::make()
                                                ->title('Integração de WhatsApp não configurada')
                                                ->body('Configure a integração de WhatsApp antes de criar templates.')
                                                ->danger()
                                                ->send();

                                            throw new \Exception('Integração de WhatsApp não configurada.');
                                        }

                                        return $integration->id;
                                    }),

                               Forms\Components\TextInput::make('name')
                                ->label('Nome')
                                ->required()
                                ->maxLength(255)
                                ->afterStateUpdated(function ($state, $set) {
                                    $formatted = Str::slug($state, '_');
                                    $set('name', $formatted);
                                })
                                ->reactive()
                                ->debounce(300),

                               Forms\Components\Select::make('language')
                                   ->label('Idioma')
                                   ->required()
                                   ->options([
                                       'pt_BR' => 'Português (Brasil)',
                                       'en_US' => 'Inglês',
                                   ])
                                   ->default('pt_BR'),

                               Forms\Components\Select::make('category')
                                   ->label('Categoria')
                                   ->required()
                                   ->options(TemplateCategoryEnum::class),

                               Forms\Components\Textarea::make('body')
                                    ->label('Mensagem')
                                    ->columnSpanFull()
                                    ->placeholder('Ex.: Olá {{name}}. Aqui é a FULANO da LOJA. Notamos que seu pedido ainda não foi finalizado. Caso precise de ajuda para concluir a compra ou tenha alguma dúvida, estamos à disposição. Um abraço, FULANO da Equipe LOJA')
                                    ->helperText('Use apenas a variável {{name}} para o nome do cliente.')
                                    ->rows(5)
                                    ->maxLength(1024)
                                    ->required(),

                               Forms\Components\Repeater::make('examples')
                                    ->label('Variáveis')
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->addable(false)
                                    ->minItems(1)
                                    ->grid(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nome da variável')
                                            ->helperText('name')
                                            ->required()
                                            ->afterStateUpdated(function ($state, $set) {
                                                $formatted = Str::slug($state, '_');
                                                $set('name', $formatted);
                                            })
                                            ->default('name')
                                            ->reactive()
                                            ->debounce(300),

                                        Forms\Components\TextInput::make('example')
                                            ->label('Exemplos de variáveis')
                                            ->helperText('Ex.: João')
                                            ->default('João')
                                            ->required(),
                                    ])
                                    ->helperText('Inclua amostras de todas as variáveis na sua mensagem para ajudar a Meta a analisar seu modelo. Para fins de proteção de privacidade, lembre-se de não incluir informações do cliente.'),
                           ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome'),

                Tables\Columns\TextColumn::make('language')
                    ->label('Idioma'),

                Tables\Columns\TextColumn::make('category')
                    ->label('Categoria')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),

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
            'index' => Pages\ListTemplates::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }

    public static function canAccess(): bool
    {
        $storeId = Filament::getTenant()->id;
        $integration = Integration::where('store_id', $storeId)
            ->where('platform_id', 7)
            ->first();

        return isset($integration);
    }
}
