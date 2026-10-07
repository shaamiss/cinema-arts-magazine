<?php

namespace App\Enums;

enum ArtCategory: string
{
    case Theatre = 'theatre';
    case Cinema = 'cinema';
    case FineArts = 'fine_arts';
    case Music = 'music';

    /**
     * Get a human-readable label for the art category.
     */
    public function label(): string
    {
        return match ($this) {
            self::Theatre => 'Theatre',
            self::Cinema => 'Cinema',
            self::FineArts => 'Fine Arts',
            self::Music => 'Music',
        };
    }
}
