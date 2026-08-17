<?php

namespace App\Actions;

use App\Models\Event;
use Illuminate\Support\Facades\DB;

class DeleteEvent
{
    public function __construct(private ArchiveEventVersion $archiveEventVersion) {}

    public function execute(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $event = Event::query()
                ->lockForUpdate()
                ->findOrFail($event->getKey());

            $this->archiveEventVersion->execute($event);
            $event->delete();
        });
    }
}
