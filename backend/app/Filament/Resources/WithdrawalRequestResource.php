<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Domain\Payouts\Models\WithdrawalRequest;
use App\Domain\Payouts\Services\PayoutService;
use App\Filament\Resources\WithdrawalRequestResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WithdrawalRequestResource extends Resource
{
    protected static ?string $model = WithdrawalRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Financial Operations';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('uuid')->disabled(),
            Forms\Components\TextInput::make('wallet_address')->disabled(),
            Forms\Components\TextInput::make('coins_amount')->disabled(),
            Forms\Components\TextInput::make('usd_amount')->disabled(),
            Forms\Components\TextInput::make('status')->disabled(),
            Forms\Components\TextInput::make('tx_hash'),
            Forms\Components\Textarea::make('rejection_reason'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user.username')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('payoutMethod.symbol')
                    ->label('Asset')
                    ->badge(),
                Tables\Columns\TextColumn::make('coins_amount')
                    ->label('Coins')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('usd_amount')
                    ->label('USD Value')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wallet_address')
                    ->label('Destination Wallet')
                    ->copyable()
                    ->limit(24),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                Tables\Columns\TextColumn::make('tx_hash')
                    ->label('TX Hash')
                    ->copyable()
                    ->limit(16),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending Review',
                        'approved' => 'Approved & Paid',
                        'rejected' => 'Rejected & Refunded',
                    ]),
            ])
            ->actions([
                // Approve & Mark Paid Action
                Tables\Actions\Action::make('approve')
                    ->label('Approve & Mark Paid')
                    ->icon('heroicon-m-check-badge')
                    ->color('success')
                    ->visible(fn (WithdrawalRequest $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\TextInput::make('tx_hash')
                            ->label('On-Chain Transaction Hash (tx_hash)')
                            ->placeholder('e.g. 0xabc123... or 3a9f...')
                            ->required(),
                    ])
                    ->action(function (WithdrawalRequest $record, array $data, PayoutService $payoutService): void {
                        try {
                            $admin = auth()->user();
                            $payoutService->approveWithdrawal($record, $admin, $data['tx_hash']);

                            Notification::make()
                                ->title('Withdrawal Approved & Finalized')
                                ->body("Payout [{$record->uuid}] marked paid on-chain.")
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Approval Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // Reject & Refund Action
                Tables\Actions\Action::make('reject')
                    ->label('Reject & Refund')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->visible(fn (WithdrawalRequest $record): bool => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Rejection & Refund Reason')
                            ->placeholder('e.g. Invalid wallet destination address or fraud flag')
                            ->required(),
                    ])
                    ->action(function (WithdrawalRequest $record, array $data, PayoutService $payoutService): void {
                        try {
                            $admin = auth()->user();
                            $payoutService->rejectWithdrawal($record, $admin, $data['rejection_reason']);

                            Notification::make()
                                ->title('Withdrawal Rejected & Refunded')
                                ->body("Coins refunded back to user [{$record->user->username}].")
                                ->warning()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Rejection Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWithdrawalRequests::route('/'),
        ];
    }
}
