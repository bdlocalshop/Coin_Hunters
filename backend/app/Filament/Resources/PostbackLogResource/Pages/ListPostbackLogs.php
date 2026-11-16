<?php

declare(strict_types=1);

namespace App\Filament\Resources\PostbackLogResource\Pages;

use App\Filament\Resources\PostbackLogResource;
use Filament\Resources\Pages\ListRecords;

class ListPostbackLogs extends ListRecords
{
    protected static string $resource = PostbackLogResource::class;
}
