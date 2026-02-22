<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExaminerVoice implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Session IDs to broadcast to (each has channel live-proctor-voice.{id}).
     *
     * @var array<int>
     */
    public array $sessionIds;

    /**
     * Base64-encoded audio chunk.
     */
    public string $chunk;

    public function __construct(array $sessionIds, string $chunk)
    {
        $this->sessionIds = array_values(array_unique(array_map('intval', $sessionIds)));
        $this->chunk = $chunk;
    }

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        foreach ($this->sessionIds as $id) {
            if ($id > 0) {
                $channels[] = new Channel('live-proctor-voice.' . $id);
            }
        }
        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'ExaminerVoice';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return ['chunk' => $this->chunk];
    }
}
