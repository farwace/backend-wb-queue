<?php

namespace App\Http\Exports;


use App\Http\Traits\CSVExportTrait;
use App\Models\Reply;
use Carbon\Carbon;

class RepliesExport implements ISimpleCsvExporter
{
    use CSVExportTrait;

    public function __construct(){}


    public function getHeadings(): array
    {
        return [
            'id',
            'Причина',
            'Номер квитанции',
            'Номер транспортного средства',
            'Номера ворот',
            'Видео',
            'Дата',
            'Направление',
        ];
    }

    public function getCount(array $arParams = []): int
    {
        $query = Reply::query();
        if(!empty($arParams['departmentId'])){
            $query->where('department_id', $arParams['departmentId']);
        }
        return $query->count('*') ?: 0;
    }

    public function query(int $chunkSize, int $offset, array $arParams = [])
    {
        $query = Reply::query();

        if(!empty($arParams['departmentId'])){
            $query->where('department_id', $arParams['departmentId']);
        }
        return $query->orderByDesc('id')->limit($chunkSize)->offset($offset)->get();
    }

    public function getRows($entities): array
    {
        $data = [];

        /** @var Reply $reply */
        foreach ($entities as $reply) {

            $row = [
                $reply->id,
                $reply->reason,
                $reply->receiptNumber,
                $reply->vehicleNumber,
                $reply->gateNumbers,
                is_array($reply->videos) ? implode(', ', array_map(function ($v){return env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET') . '/' . $v;}, $reply->videos)) : $reply->videos,
                Carbon::parse($reply->created_at)->timezone('Europe/Moscow')->toDateTimeString(),
                $reply->department ? $reply->department->name : '',
            ];

            $data[] = $row;
        }
        return $data;
    }
}
