<?php

namespace App\Enums;

enum PublicationStatus: string
{
    case Draft = 'draft';
    case Review = 'review';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Get a human-readable label for the publication status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Review => 'In Review',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }
}
