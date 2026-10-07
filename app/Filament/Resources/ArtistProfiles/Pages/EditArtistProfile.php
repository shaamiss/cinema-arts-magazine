<?php

namespace App\Filament\Resources\ArtistProfiles\Pages;

use App\Filament\Resources\ArtistProfiles\ArtistProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditArtistProfile extends EditRecord
{
    protected static string $resource = ArtistProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
