<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoResource\Pages;
use App\Models\Video;
use App\Services\FileDeleteService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class VideoResource extends Resource
{
    protected static ?string $model = Video::class;
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Content Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Video Information')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Enter video title'),

                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->maxLength(65535)
                            ->placeholder('Enter video description')
                            ->rows(4),

                        Forms\Components\FileUpload::make('video_url')
                            ->required()
                            ->directory('videos')
                            ->acceptedFileTypes(['video/mp4', 'video/quicktime'])
                            ->maxSize(100000) // 100MB
                            ->downloadable()
                            ->label('Video File'),

                        Forms\Components\FileUpload::make('thumbnail_url')
                            ->directory('thumbnails')
                            ->image()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1280')
                            ->imageResizeTargetHeight('720')
                            ->maxSize(5000) // 5MB
                            ->label('Thumbnail'),

                        Forms\Components\Toggle::make('is_active')
                            ->required()
                            ->default(true)
                            ->label('Active Status')
                            ->helperText('Toggle to control video visibility'),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn () => auth()->id())
                            ->required(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Thumbnail')
                    ->square(),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status')
                    ->sortable(),

                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('likes_count')
                    ->label('Likes')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('comments_count')
                    ->label('Comments')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Videos')
                    ->trueLabel('Active Videos')
                    ->falseLabel('Inactive Videos'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->placeholder(fn ($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                        Forms\Components\DatePicker::make('created_until')
                            ->placeholder(fn ($state): string => now()->format('M d, Y')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Video $record) {
                        // Delete video file before deleting the record
                        FileDeleteService::deleteStorageFile($record->video_url, 'video');
                        
                        // Delete thumbnail file
                        FileDeleteService::deleteStorageFile($record->thumbnail_url, 'thumbnail');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (array $records) {
                            foreach ($records as $record) {
                                // Delete video file before deleting the record
                                FileDeleteService::deleteStorageFile($record->video_url, 'video');
                                
                                // Delete thumbnail file
                                FileDeleteService::deleteStorageFile($record->thumbnail_url, 'thumbnail');
                            }
                        }),
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
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationItems(): array
    {
        return parent::getNavigationItems();
    }
}