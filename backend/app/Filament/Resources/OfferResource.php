<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Offerwalls\Models\Offer;
use App\Filament\Resources\OfferResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Network Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('title')->required(),
            Forms\Components\TextInput::make('provider_slug')->disabled(),
            Forms\Components\TextInput::make('network_payout_usd')->numeric()->prefix('$'),
            Forms\Components\TextInput::make('user_payout_coins')->numeric()->suffix('Coins'),
            Forms\Components\Textarea::make('description'),
            Forms\Components\Toggle::make('is_featured')->label('Pin as Featured Offer'),
            Forms\Components\Toggle::make('is_active')->label('Active'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('provider_slug')->badge()->label('Provider'),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('network_payout_usd')->money('USD')->sortable()->label('Net USD'),
                Tables\Columns\TextColumn::make('user_payout_coins')->numeric()->sortable()->label('Coins'),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured'),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('epc_score')->numeric()->sortable()->label('EPC'),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('provider_slug')
                    ->options([
                        'offerwall_gg' => 'Offerwall.gg',
                        'offerwall_com' => 'Offerwall.com',
                        'lootably' => 'Lootably',
                        'bitlabs' => 'BitLabs',
                        'cpx_research' => 'CPX Research',
                    ]),
                Tables\Filters\TernaryFilter::make('is_featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOffers::route('/'),
        ];
    }
}
