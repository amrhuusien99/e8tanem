<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationResource\Pages;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;

class NotificationResource extends Resource
{
    protected static ?string $model = Notification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    
    protected static ?string $navigationGroup = 'User Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->maxLength(1000)
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->options([
                        'general' => 'General',
                        'video' => 'New Video',
                        'update' => 'App Update',
                    ])
                    ->required()
                    ->default('general'),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->nullable()
                    ->helperText('Leave empty to send to all users')
                    ->label('Send to User'),
                Forms\Components\DateTimePicker::make('expires_at')
                    ->label('Expiration Date')
                    ->helperText('When this notification should expire and no longer be shown to users')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('global')
                    ->label('Audience')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->user_id === null ? 'Global' : 'Specific User';
                    })
                    ->colors([
                        'success' => function ($state, $record) {
                            return $record->user_id === null;
                        },
                    ]),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Sent To')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->user_id ? $state : 'All Users';
                    })
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_read')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'general' => 'General',
                        'video' => 'New Video',
                        'update' => 'App Update',
                    ]),
                Tables\Filters\SelectFilter::make('audience')
                    ->options([
                        'global' => 'Global (All Users)',
                        'specific' => 'Specific User',
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['value']) {
                            'global' => $query->whereNull('user_id'),
                            'specific' => $query->whereNotNull('user_id'),
                            default => $query,
                        };
                    }),
                Tables\Filters\TernaryFilter::make('is_read'),
                Tables\Filters\Filter::make('expiration')
                    ->form([
                        Forms\Components\Select::make('expired')
                            ->options([
                                'active' => 'Not Expired',
                                'expired' => 'Expired',
                                'no_expiry' => 'No Expiration Date',
                            ])
                            ->default('active'),
                    ])
                    ->query(function ($query, array $data) {
                        return match ($data['expired']) {
                            'active' => $query->where(function ($query) {
                                $query->whereNull('expires_at')
                                    ->orWhere('expires_at', '>', now());
                            }),
                            'expired' => $query->where('expires_at', '<=', now()),
                            'no_expiry' => $query->whereNull('expires_at'),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotifications::route('/'),
            'create' => Pages\CreateNotification::route('/create'),
            'edit' => Pages\EditNotification::route('/{record}/edit'),
        ];
    }
}
