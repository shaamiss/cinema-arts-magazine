<?php

namespace App\Filament\Resources\ArtistProfiles\Pages;

use App\Filament\Resources\ArtistProfiles\ArtistProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListArtistProfiles extends ListRecords
{
    protected static string $resource = ArtistProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
