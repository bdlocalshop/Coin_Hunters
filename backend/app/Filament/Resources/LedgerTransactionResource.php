<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Ledger\Models\LedgerTransaction;
use App\Filament\Resources\LedgerTransactionResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LedgerTransactionResource extends Resource
{
    protected static ?string $model = LedgerTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Financial Operations';
    protected static ?string $navigationLabel = 'Ledger Audit';
    protected static ?int $navigationSort = 2;

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
                Tables\Columns\TextColumn::make('uuid')->label('UUID')->limit(12)->copyable(),
                Tables\Columns\TextColumn::make('type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('reference_type')->label('Ref Type'),
                Tables\Columns\TextColumn::make('reference_id')->label('Ref ID')->limit(16),
                Tables\Columns\TextColumn::make('description')->limit(36)->searchable(),
                Tables\Columns\TextColumn::make('idempotency_key')->limit(16)->copyable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'offer_completion' => 'Offer Completion',
                        'offer_pending' => 'Offer Pending',
                        'signup_bonus' => 'Signup Bonus',
                        'referral_bonus' => 'Referral Bonus',
                        'withdrawal_request' => 'Withdrawal Request',
                        'withdrawal_approved' => 'Withdrawal Approved',
                        'withdrawal_rejected' => 'Withdrawal Rejected',
                        'chargeback' => 'Chargeback',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLedgerTransactions::route('/'),
        ];
    }
}
