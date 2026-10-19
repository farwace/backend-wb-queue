<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param string $directionCode код направления (например e1)
     * @param int $orderId уникальный id заказа (queue.id) — ключ элемента на экране грузчика
     * @param bool $isClosed закрыт ли заказ
     * @param string|null $squareCode код квадрата
     * @param string|null $squareName название/номер квадрата
     * @param string $workerName имя сотрудника (получателя палета)
     * @param \DateTime|null $timestamp
     * @param string|null $color цвет грузчика
     * @param string|null $name имя грузчика
     */
    public function __construct(
        public string $directionCode,
        public int $orderId,
        public bool $isClosed,
        public ?string $squareCode,
        public ?string $squareName,
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
                'id' => $this->orderId,
                'isClosed' => $this->isClosed,
            ];
        }
        return [
            'id' => $this->orderId,
            'isClosed' => $this->isClosed,
            'squareCode' => $this->squareCode,
            'squareName' => $this->squareName,
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
