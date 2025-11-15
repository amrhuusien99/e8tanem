<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PendingVideoResource\Pages;
use App\Models\Video;
use App\Notifications\PendingVideoRejected;
use App\Services\FileDeleteService;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PendingVideoResource extends Resource
{
    protected static ?string $model = Video::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 2;
    protected static ?string $label = 'Pending Video';
    protected static ?string $pluralLabel = 'Pending Videos';
    protected static ?string $navigationLabel = 'Pending Videos';

    public static function form(Form $form): Form
    {
        return $form;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Thumbnail')
                    ->disk('public')
                    ->rounded()
                    ->height(60)
                    ->width(60),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Uploader')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->toggleable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Uploader')
                    ->relationship('user', 'name'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')
                            ->label('From'),
                        DatePicker::make('until')
                            ->label('Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'] ?? null,
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-play-circle')
                    ->url(fn (Video $record): string => Storage::url($record->video_url))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('accept')
                    ->label('Accept')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (Video $record): void {
                        $record->update(['is_active' => true]);
                    }),

                Tables\Actions\DeleteAction::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->requiresConfirmation()
                    ->after(function (Video $record): void {
                        if ($record->relationLoaded('user') ? $record->user : $record->user()->exists()) {
                            optional($record->user)->notify(new PendingVideoRejected($record));
                        }
                        FileDeleteService::deleteStorageFile($record->video_url, 'video');
                        FileDeleteService::deleteStorageFile($record->thumbnail_url, 'thumbnail');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('accept_selected')
                        ->label('Accept Selected')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (Collection $records): void {
                            $records->each(function (Model $record): void {
                                if ($record instanceof Video) {
                                    optional($record->user)->notify(new PendingVideoRejected($record));
                                }
                                FileDeleteService::deleteStorageFile($record->video_url, 'video');
                                FileDeleteService::deleteStorageFile($record->thumbnail_url, 'thumbnail');
                            });
                        }),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePendingVideos::route('/'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_active', false)
            ->with('user');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('is_active', false)->count();

        return $count > 0 ? (string) $count : null;
    }
}

