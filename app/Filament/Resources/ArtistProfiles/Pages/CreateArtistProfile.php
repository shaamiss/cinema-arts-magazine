<?php

namespace App\Filament\Resources\ArtistProfiles\Pages;

use App\Filament\Resources\ArtistProfiles\ArtistProfileResource;
use Filament\Resources\Pages\CreateRecord;

class CreateArtistProfile extends CreateRecord
{
    protected static string $resource = ArtistProfileResource::class;
}
