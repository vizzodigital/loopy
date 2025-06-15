<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Enums\ConversationStatusEnum;
use App\Filament\Resources\ConversationResource\Pages;
use App\Models\Conversation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ConversationResource extends Resource
{
    protected static ?string $tenantRelationshipName = 'store';

    protected static ?string $model = Conversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $modelLabel = 'Recuperação';

    protected static ?string $pluralModelLabel = 'Recuperações';

    protected static ?string $navigationLabel = 'Recuperações';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'ATENDIMENTOS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([

                        Forms\Components\Select::make('abandoned_cart_id')
                            ->label('Carrinho Abandonado')
                            ->relationship('abandonedCart', 'id')
                            ->disabled()
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(ConversationStatusEnum::class)
                            ->disabled()
                            ->required(),

                        Forms\Components\DateTimePicker::make('started_at')
                            ->label('Iniciado em')
                            ->readOnly()
                            ->required(),

                        Forms\Components\DateTimePicker::make('closed_at')
                            ->label('Encerrado em')
                            ->readOnly(),

                        Forms\Components\DateTimePicker::make('human_assumed_at')
                            ->label('Assumido pelo humano em')
                            ->readOnly(),

                        Forms\Components\Select::make('human_user_id')
                            ->label('Usuário')
                            ->relationship('humanUser', 'name')
                            ->disabled()
                            ->default(null),

                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->columns([

                Tables\Columns\TextColumn::make('abandonedCart.id')
                    ->label('Carrinho Abandonado')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('started_at')
                    ->label('Iniciado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Encerrado em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('human_assumed_at')
                    ->label('Assumido pelo humano em')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),

                Tables\Columns\TextColumn::make('humanUser.name')
                    ->label('Usuário')
                    ->numeric()
                    ->sortable(),

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
            'index' => Pages\ListConversations::route('/'),
            'create' => Pages\CreateConversation::route('/create'),
            'edit' => Pages\EditConversation::route('/{record}/edit'),
            'conversationMessages' => Pages\ConversationMessages::route('/{record}/conversation-messages'),
        ];
    }
}
