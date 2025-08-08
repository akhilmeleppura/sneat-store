<?php

namespace Modules\Accounting\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Accounting\App\Models\OpeningBalance;

class EntryCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

     public $entry;

 
    /**
     * Create a new event instance.
     */
   public function __construct(OpeningBalance $entry)
    {
        $this->entry = $entry;
    }
    /**
     * Get the channels the event should be broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
