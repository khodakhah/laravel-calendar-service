<?php

namespace App\Actions;

use App\Models\Event;
use App\Models\EventChangeLog;

class ArchiveEventVersion
{
    public function execute(Event $event): void
    {
        EventChangeLog::create([
            'event_id' => $event->id,
            'revision' => $event->revision,
            'snapshot' => $event->attributesToArray(),
            'archived_at' => now(),
        ]);
    }
}
