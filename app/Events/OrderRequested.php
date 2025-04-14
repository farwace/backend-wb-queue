<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $directionCode,
        public int $id,
        public bool $isClosed,
        public string $tableCode,
        public string $tableName,
        public string $workerName,
        public ?\DateTime $timestamp,
        public ?string $color = '',
        public ?string $name = '')
    {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): Channel
    {
        return new Channel('orders');
    }

    public function broadcastWith(): array
    {
        if($this->isClosed){
            return [
                'id' => $this->id,
                'isClosed' => $this->isClosed,
            ];
        }
        return [
            'id' => $this->id,
            'isClosed' => $this->isClosed,
            'tableCode' => $this->tableCode,
            'tableName' => $this->tableName,
            'workerName' => $this->workerName,
            'timestamp' => $this->timestamp,
            'color' => $this->color,
            'name' => $this->name,
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.requested.' . $this->directionCode;
    }
}
