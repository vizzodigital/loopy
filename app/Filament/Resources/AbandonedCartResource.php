<?php

declare(strict_types = 1);

namespace App\Filament\Resources;

use App\Enums\CartStatusEnum;
use App\Filament\Resources\AbandonedCartResource\Pages;
use App\Models\AbandonedCart;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AbandonedCartResource extends Resource
{
    protected static ?string $tenantOwnershipRelationshipName = 'store';

    protected static ?string $model = AbandonedCart::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $modelLabel = 'Checkout abandonado';

    protected static ?string $pluralModelLabel = 'Checkouts abandonados';

    protected static ?string $navigationLabel = 'Checkouts abandonados';

    protected static ?int $navigationSort = 6;

    protected static ?string $navigationGroup = 'ATENDIMENTOS';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make()
                    ->columns(2)
                    ->schema([

                        Forms\Components\Select::make('store_id')
                            ->label('Loja')
                            ->disabled()
                            ->relationship('store', 'name')
                            ->required(),

                        Forms\Components\Select::make('customer_id')
                            ->label('Cliente')
                            ->disabled()
                            ->relationship('customer', 'name')
                            ->required(),

                        Forms\Components\Select::make('abandonment_reason_id')
                            ->label('Motivo')
                            ->disabled()
                            ->relationship('abandonmentReason', 'name')
                            ->required(),

                        Forms\Components\TextInput::make('external_cart_id')
                            ->label('ID externo do carrinho')
                            ->maxLength(255)
                            ->readOnly()
                            ->default(null),

                        Forms\Components\TextInput::make('total_amount')
                            ->label('Valor total')
                            ->numeric()
                            ->readOnly()
                            ->default(null),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options(CartStatusEnum::class)
                            ->disabled()
                            ->required(),

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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('abandonmentReason.description')
                    ->label('Motivo')
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('external_cart_id')
                    ->label('ID Externo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Valor total')
                    ->money('BRL', locale: 'pt_BR')
                    ->alignEnd()
                    ->sortable(),

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
            'index' => Pages\ListAbandonedCarts::route('/'),
            'create' => Pages\CreateAbandonedCart::route('/create'),
            'edit' => Pages\EditAbandonedCart::route('/{record}/edit'),
        ];
    }
}
