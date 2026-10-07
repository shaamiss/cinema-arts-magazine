<?php

namespace App\Filament\Resources\MediaGalleries\Pages;

use App\Filament\Resources\MediaGalleries\MediaGalleryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMediaGallery extends EditRecord
{
    protected static string $resource = MediaGalleryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
