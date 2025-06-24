<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Models\Agent;
use App\Models\Store;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgentResource extends Resource
{
    protected static ?string $tenantOwnershipRelationshipName = 'stores';

    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';

    protected static ?string $modelLabel = 'Agente';

    protected static ?string $pluralModelLabel = 'Agentes';

    protected static ?string $navigationLabel = 'Agentes';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'AGENTES DE IA';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(7)
            ->schema([
                Forms\Components\Group::make()
                    ->columnSpan(5)
                    ->schema([

                        Forms\Components\Tabs::make()
                            ->columnSpanFull()
                            ->tabs([

                                Forms\Components\Tabs\Tab::make('Dados gerais')
                                    ->columns(2)
                                    ->schema([

                                        Forms\Components\Hidden::make('user_id')
                                            ->default(auth()->guard('web')->user()->id)
                                            ->required(),

                                        Forms\Components\TextInput::make('name')
                                            ->label('Nome')
                                            ->required()
                                            ->maxLength(50),

                                        Forms\Components\TextInput::make('description')
                                            ->label('Descrição')
                                            ->maxLength(255),

                                        Forms\Components\Select::make('model')
                                            ->label('Modelo')
                                            ->required()
                                            ->options([
                                                'gpt-3.5-turbo' => 'gpt-3.5-turbo',
                                                'gpt-4-turbo' => 'gpt-4-turbo',
                                                'gpt-4o' => 'gpt-4o',
                                                'gpt-4o-mini' => 'gpt-4o-mini (Recomendado)',
                                                'gpt-4.1' => 'gpt-4.1',
                                                'gpt-4.1-mini' => 'gpt-4.1-mini',
                                            ])
                                            ->default('gpt-4o-mini'),

                                        Forms\Components\Select::make('temperature')
                                            ->label('Temperatura')
                                            ->hint('Criatividade do agente')
                                            ->hintColor('primary')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: '(0.0–1.0): Define a aleatoriedade das respostas. Valores baixos (e.g. 0.0–0.3) tornam o modelo mais determinístico e factual, ideais para respostas precisas. Valores médios (0.4–0.6) dão um tom mais conversacional e amigável. Recomendação: para atendimento, normalmente usamos 0.2–0.5 (respostas úteis sem muita criatividade).')
                                            ->required()
                                            ->options([
                                                '0.0' => '0.0',
                                                '0.1' => '0.1',
                                                '0.2' => '0.2',
                                                '0.3' => '0.3',
                                                '0.4' => '0.4 (Recomendado)',
                                                '0.5' => '0.5',
                                                '0.6' => '0.6',
                                                '0.7' => '0.7',
                                                '0.8' => '0.8',
                                                '0.9' => '0.9',
                                                '1.0' => '1.0',
                                            ])
                                            ->preload()
                                            ->searchable()
                                            ->default('0.4'),

                                        Forms\Components\Textarea::make('system_prompt')
                                            ->label('Prompt de sistema')
                                            ->rows(5)
                                            ->columnSpanFull(),
                                    ]),

                                Forms\Components\Tabs\Tab::make('Avançado')
                                    ->columns(3)
                                    ->schema([

                                        Forms\Components\Hidden::make('top_p')
                                            ->label('Top P')
                                            ->default(1),

                                        Forms\Components\Select::make('frequency_penalty')
                                            ->label('Penaliza repetição')
                                            ->hint('Penaliza a repetição de tokens já usados.')
                                            ->hintColor('primary')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: '(–2.0–2.0): Penaliza a repetição de tokens já usados. Valores próximos de 0 (ex. 0 ou 0.2) costumam ser suficientes para evitar repetições excessivas sem mudar muito o texto.')
                                            ->required()
                                            ->options([
                                                '0.0' => '0.0',
                                                '0.1' => '0.1',
                                                '0.2' => '0.2',
                                                '0.3' => '0.3 (Recomendado)',
                                                '0.4' => '0.4',
                                                '0.5' => '0.5',
                                                '0.6' => '0.6',
                                                '0.7' => '0.7',
                                                '0.8' => '0.8',
                                                '0.9' => '0.9',
                                                '1.0' => '1.0',
                                            ])
                                            ->preload()
                                            ->searchable()
                                            ->default('0.3'),

                                        Forms\Components\Select::make('presence_penalty')
                                            ->label('Penaliza presença')
                                            ->hint('Penaliza repetição de tópicos/ideias.')
                                            ->hintColor('primary')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: '(–2.0–2.0): Penaliza repetição de tópicos/idéias. Em geral é deixado em 0 ou leve (até ~0.5) caso queira incentivar o modelo a trazer conceitos novos em cada resposta.')
                                            ->required()
                                            ->options([
                                                '0.0' => '0.0 (Recomendado)',
                                                '0.1' => '0.1',
                                                '0.2' => '0.2',
                                                '0.3' => '0.3',
                                                '0.4' => '0.4',
                                                '0.5' => '0.5',
                                                '0.6' => '0.6',
                                                '0.7' => '0.7',
                                                '0.8' => '0.8',
                                                '0.9' => '0.9',
                                                '1.0' => '1.0',
                                            ])
                                            ->preload()
                                            ->searchable()
                                            ->default('0.0'),

                                        Forms\Components\TextInput::make('max_tokens')
                                            ->label('Máximo de Tokens')
                                            ->hint('Máximo de Tokens por resposta')
                                            ->hintColor('primary')
                                            ->hintIcon('heroicon-m-question-mark-circle', tooltip: 'Número máximo de tokens na resposta. Deve ser dimensionado de acordo com o comprimento esperado da resposta. Por exemplo, 256–512 para respostas curtas; 1024 ou mais para respostas detalhadas. É comum definir um valor um pouco maior que o esperado para evitar corte prematuro. Ajuste conforme o caso de uso (lembre que tokens excedentes também custam)')
                                            ->required()
                                            ->numeric()
                                            ->minValue('256')
                                            ->maxValue('2048')
                                            ->default('600'),

                                    ]),

                            ]),

                    ]),

                Forms\Components\Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Forms\Components\Section::make()
                            ->schema([
                                Forms\Components\Select::make('stores')
                                    ->label('Lojas')
                                    ->relationship(
                                        'stores',
                                        'name',
                                        fn (Builder $query) => $query->where('store_id', Filament::getTenant()->id)
                                    )
                                    ->required()
                                    ->multiple()
                                    ->preload()
                                    ->searchable()
                                    ->pivotData([
                                        'is_active' => false,
                                        'assigned_by' => auth()->guard('web')->id(),
                                        'assigned_at' => now(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->searchable(false)
            ->paginated(false)
            ->columns([

                Tables\Columns\TextColumn::make('stores.name')
                    ->label('Loja')
                    ->badge()
                    ->color('primary')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->badge(),

                Tables\Columns\SelectColumn::make('model')
                    ->label('Modelo')
                    ->options([
                        'gpt-3.5-turbo' => 'gpt-3.5-turbo',
                        'gpt-4-turbo' => 'gpt-4-turbo',
                        'gpt-4o' => 'gpt-4o',
                        'gpt-4o-mini' => 'gpt-4o-mini (Recomendado)',
                        'gpt-4.1' => 'gpt-4.1',
                        'gpt-4.1-mini' => 'gpt-4.1-mini',
                    ]),

                Tables\Columns\ToggleColumn::make('is_active_for_current_store')
                    ->label('Ativo?')
                    ->alignCenter()
                    ->updateStateUsing(function (Agent $record, $state) {
                        $storeId = Filament::getTenant()->id;

                        $record->stores()->updateExistingPivot($storeId, [
                            'is_active' => $state,
                            'assigned_by' => auth()->guard('web')->id(),
                            'assigned_at' => now(),
                        ]);

                        return $state;
                    }),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Agente Infynia?')
                    ->alignCenter()
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (Agent $record): bool => $record->is_default === false),

                // Tables\Actions\Action::make('activate')
                //     ->label('Ativar')
                //     ->color('success')
                //     ->visible(fn (Agent $record): bool => $record->is_active_for_current_store === false)
                //     ->action(function (Agent $record) {
                //         $storeId = Filament::getTenant()->id;

                //         $record->stores()->updateExistingPivot($storeId, [
                //             'is_active' => true,
                //             'assigned_by' => auth()->guard('web')->id(),
                //             'assigned_at' => now(),
                //         ]);
                //     }),
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
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
