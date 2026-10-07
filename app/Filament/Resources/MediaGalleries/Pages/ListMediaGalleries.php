<?php

namespace App\Filament\Resources\MediaGalleries\Pages;

use App\Filament\Resources\MediaGalleries\MediaGalleryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaGalleries extends ListRecords
{
    protected static string $resource = MediaGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
