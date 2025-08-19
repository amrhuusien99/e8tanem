<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class QuranVerseWidget extends Widget
{
    protected static string $view = 'filament.widgets.quran-verse-widget';

    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return true;
    }
}