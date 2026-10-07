<?php

namespace App\Events;

use App\Models\Issue;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IssuePagesProcessedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  list<string>  $pageUrls
     */
    public function __construct(
        public Issue $issue,
        public int $pagesCount,
        public array $pageUrls = []
    ) {}
}
