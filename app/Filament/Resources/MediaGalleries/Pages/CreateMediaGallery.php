<?php

namespace App\Filament\Resources\MediaGalleries\Pages;

use App\Enums\MediaType;
use App\Filament\Resources\MediaGalleries\MediaGalleryResource;
use App\Jobs\OptimizeMediaImageJob;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateMediaGallery extends CreateRecord
{
    protected static string $resource = MediaGalleryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['size_bytes']) && ! empty($data['file_path'])) {
            $disk = Storage::disk('public');
            if ($disk->exists($data['file_path'])) {
                $data['size_bytes'] = $disk->size($data['file_path']);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->type === MediaType::Image) {
            OptimizeMediaImageJob::dispatch($this->record);
        }
    }
}
