<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
class QueueResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'isClosed' => $this->is_closed,
            'workerName' => $this->worker->name,
            'squareName' => $this->square->name ?? $this->square->code,
            'squareCode' => $this->square->code,
            'timestamp' => $this->updated_at,
            'color' => $this->color,
            'name' => $this->name
        ];
    }
}
