<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Models\Agent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

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
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('description')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('model')
                    ->required()
                    ->maxLength(255)
                    ->default('gpt-3.5-turbo'),
                Forms\Components\TextInput::make('temperature')
                    ->required()
                    ->numeric()
                    ->default(0.70),
                Forms\Components\TextInput::make('top_p')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('frequency_penalty')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('presence_penalty')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('max_tokens')
                    ->required()
                    ->numeric()
                    ->default(1000),
                Forms\Components\Textarea::make('system_prompt')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_test')
                    ->required(),
                Forms\Components\Toggle::make('is_default')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuário')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->badge()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descrição')
                    ->searchable(),

                Tables\Columns\TextColumn::make('model')
                    ->label('Modelo')
                    ->badge()
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active_for_current_store')
                    ->label('Ativo?')
                    ->alignCenter()
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Agente Infynia?')
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
                Tables\Actions\EditAction::make()
                ->visible(fn (Agent $record): bool => $record->is_default === false),
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
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
