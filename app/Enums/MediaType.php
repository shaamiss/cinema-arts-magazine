<?php

namespace App\Enums;

enum MediaType: string
{
    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';
    case Pdf = 'pdf';

    /**
     * Get a human-readable label for the media type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Image => 'Image',
            self::Audio => 'Audio',
            self::Video => 'Video',
            self::Pdf => 'PDF Document',
        };
    }
}
