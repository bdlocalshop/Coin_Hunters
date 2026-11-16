<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Offerwalls\Models\OfferwallProvider;
use App\Filament\Resources\OfferwallProviderResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OfferwallProviderResource extends Resource
{
    protected static ?string $model = OfferwallProvider::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';
    protected static ?string $navigationGroup = 'Network Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('slug')->disabled(),
            Forms\Components\TextInput::make('payout_margin_percent')
                ->label('Payout Margin (%)')
                ->numeric()
                ->required()
                ->helperText('Percentage of network payout passed through to users (e.g. 70%).'),
            Forms\Components\TextInput::make('bonus_multiplier')
                ->label('Bonus Multiplier')
                ->numeric()
                ->step(0.01)
                ->helperText('Temporary weekend or promotional boost (e.g. 1.15 = +15%).'),
            Forms\Components\TextInput::make('secret_key')->password(),
            Forms\Components\TextInput::make('api_key')->password(),
            Forms\Components\TextInput::make('app_id'),
            Forms\Components\Toggle::make('is_active')->label('Enabled'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->badge(),
                Tables\Columns\TextColumn::make('payout_margin_percent')
                    ->label('Margin')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bonus_multiplier')
                    ->label('Multiplier')
                    ->suffix('x')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOfferwallProviders::route('/'),
        ];
    }
}
