<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('username')->required(),
            Forms\Components\TextInput::make('email')->email()->required(),
            Forms\Components\Select::make('status')
                ->options([
                    'active' => 'Active',
                    'suspended' => 'Suspended',
                    'banned' => 'Banned',
                ])
                ->required(),
            Forms\Components\TextInput::make('fraud_score')->numeric(),
            Forms\Components\Toggle::make('is_admin')->label('Administrator Privileges'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('username')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('available_coins')->numeric()->sortable()->label('Available'),
                Tables\Columns\TextColumn::make('pending_coins')->numeric()->sortable()->label('Pending'),
                Tables\Columns\TextColumn::make('locked_withdrawal_coins')->numeric()->label('Locked'),
                Tables\Columns\TextColumn::make('lifetime_earned_coins')->numeric()->sortable()->label('Lifetime Earned'),
                Tables\Columns\TextColumn::make('fraud_score')->numeric()->sortable()->label('Risk Score'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'suspended' => 'warning',
                        'banned' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('country_code')->label('Geo'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'banned' => 'Banned',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('ban')
                    ->label('Ban User')
                    ->icon('heroicon-m-no-symbol')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->status !== 'banned')
                    ->action(function (User $record): void {
                        $record->status = 'banned';
                        $record->save();
                        Notification::make()->title('User has been banned.')->danger()->send();
                    }),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (User $record): bool => $record->status !== 'active')
                    ->action(function (User $record): void {
                        $record->status = 'active';
                        $record->save();
                        Notification::make()->title('User activated.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
        ];
    }
}
