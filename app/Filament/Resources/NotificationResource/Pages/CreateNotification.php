<?php

namespace App\Filament\Resources\NotificationResource\Pages;

use App\Filament\Resources\NotificationResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification as FilamentNotification;

class CreateNotification extends CreateRecord
{
    protected static string $resource = NotificationResource::class;

    protected function afterCreate(): void
    {
        FilamentNotification::make()
            ->success()
            ->title('Notification created successfully')
            ->send();
    }

    // Removed the mutateFormDataBeforeCreate method since user_id is now required
}
