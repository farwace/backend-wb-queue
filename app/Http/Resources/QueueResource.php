<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
class QueueResource extends JsonResource{
    public function toArray($request){
        return [
            'id' => $this->id,
            'isClosed' => $this->is_closed,
            'workerName' => $this->worker->name,
            'tableName' => $this->table->name,
            'tableCode' => $this->table->code,
        ];
    }
}
