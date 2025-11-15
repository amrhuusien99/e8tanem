<?php

namespace App\Filament\Resources\PendingVideoResource\Pages;

use App\Filament\Resources\PendingVideoResource;
use Filament\Resources\Pages\ListRecords;

class ManagePendingVideos extends ListRecords
{
    protected static string $resource = PendingVideoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getTitle(): string
    {
        return __('Pending Videos');
    }
}

