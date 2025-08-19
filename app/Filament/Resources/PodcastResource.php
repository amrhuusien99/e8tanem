<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PodcastResource\Pages;
use App\Models\Podcast;
use App\Services\FileDeleteService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PodcastResource extends Resource
{
    protected static ?string $model = Podcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-musical-note';
    protected static ?string $navigationGroup = 'Content Management';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),

                Forms\Components\FileUpload::make('audio_url')
                    ->label('Audio File')
                    ->required()
                    ->acceptedFileTypes(['audio/mpeg', 'audio/mp3', 'audio/wav'])
                    ->directory('podcasts')
                    ->maxSize(50000) // 50MB max
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('thumbnail_url')
                    ->label('Thumbnail')
                    ->image()
                    ->directory('podcast-thumbnails'),

                    Forms\Components\Select::make('category')
                    ->label('التصنيف')
                    ->required()
                    ->options([
                        'القرآن الكريم' => 'القرآن الكريم',
                        'الحديث الشريف' => 'الحديث الشريف',
                        'السيرة النبوية' => 'السيرة النبوية',
                        'الفقه الإسلامي' => 'الفقه الإسلامي',
                        'العقيدة' => 'العقيدة',
                        'محاضرات دينية' => 'محاضرات دينية',
                        'الفتاوى والاستشارات' => 'الفتاوى والاستشارات',
                        'أدعية وأذكار' => 'أدعية وأذكار',
                        'التاريخ الإسلامي' => 'التاريخ الإسلامي',
                        'تطوير الذات الإسلامي' => 'تطوير الذات الإسلامي',
                        'الأسرة والتربية الإسلامية' => 'الأسرة والتربية الإسلامية',
                        'قضايا الشباب' => 'قضايا الشباب',
                        'تحفيز وإلهام' => 'تحفيز وإلهام',
                        'الأخلاق والقيم' => 'الأخلاق والقيم',
                        'أخرى' => 'أخرى',
                    ]),

                Forms\Components\RichEditor::make('description')
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Forms\Components\Hidden::make('user_id')
                    ->default(fn () => auth()->id()),
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

                Tables\Columns\TextColumn::make('category')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Author')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Status')
                    ->sortable(),

                Tables\Columns\TextColumn::make('plays_count')
                    ->label('Plays')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options([
                        'education' => 'Education',
                        'technology' => 'Technology',
                        'business' => 'Business',
                        'entertainment' => 'Entertainment',
                        'news' => 'News',
                        'other' => 'Other',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Podcast $record) {
                        // Delete audio file before deleting the record
                        FileDeleteService::deleteStorageFile($record->audio_url, 'audio');
                        
                        // Delete thumbnail file
                        FileDeleteService::deleteStorageFile($record->thumbnail_url, 'thumbnail');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->before(function (array $records) {
                            foreach ($records as $record) {
                                // Delete audio file before deleting the record
                                FileDeleteService::deleteStorageFile($record->audio_url, 'audio');
                                
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
            'index' => Pages\ListPodcasts::route('/'),
            'create' => Pages\CreatePodcast::route('/create'),
            'edit' => Pages\EditPodcast::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}