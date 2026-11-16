<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Offerwalls\Models\PostbackLog;
use App\Filament\Resources\PostbackLogResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PostbackLogResource extends Resource
{
    protected static ?string $model = PostbackLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';
    protected static ?string $navigationGroup = 'Network Management';
    protected static ?string $navigationLabel = 'Postback Logs';
    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('provider_slug')->badge()->label('Provider'),
                Tables\Columns\TextColumn::make('external_transaction_id')->label('TX ID')->limit(16)->copyable(),
                Tables\Columns\TextColumn::make('ip_address')->label('IP'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'credited' => 'success',
                        'held' => 'warning',
                        'duplicate' => 'info',
                        'reversed' => 'danger',
                        'rejected_ip', 'rejected_sig', 'error' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('message')->limit(36),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'credited' => 'Credited',
                        'held' => 'Held (High Value)',
                        'duplicate' => 'Duplicate',
                        'rejected_ip' => 'Rejected (IP)',
                        'rejected_sig' => 'Rejected (Signature)',
                        'reversed' => 'Reversed (Chargeback)',
                        'error' => 'Error',
                    ]),
                Tables\Filters\SelectFilter::make('provider_slug')
                    ->options([
                        'offerwall_gg' => 'Offerwall.gg',
                        'offerwall_com' => 'Offerwall.com',
                        'lootably' => 'Lootably',
                        'bitlabs' => 'BitLabs',
                        'cpx_research' => 'CPX Research',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPostbackLogs::route('/'),
        ];
    }
}
