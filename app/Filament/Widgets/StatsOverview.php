<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Video;
use App\Models\Comment;
use App\Models\Subject;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Total registered users')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Total Videos', Video::count())
                ->description('Total uploaded videos')
                ->descriptionIcon('heroicon-m-video-camera')
                ->color('warning'),

            Stat::make('Total Comments', Comment::count())
                ->description('Total user comments')
                ->descriptionIcon('heroicon-m-chat-bubble-left-right')
                ->color('info'),

            Stat::make('Total Subjects', Subject::count())
                ->description('Total educational subjects')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Active Videos', Video::where('is_active', true)->count())
                ->description('Currently active videos')
                ->descriptionIcon('heroicon-m-play')
                ->color('success'),

            Stat::make('Most Viewed Video', function() {
                $video = Video::orderBy('views_count', 'desc')->first();
                return $video ? $video->views_count . ' views' : 'No views';
            })
                ->description('Highest video views')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color('danger'),
        ];
    }
}