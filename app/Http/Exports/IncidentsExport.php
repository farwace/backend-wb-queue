<?php

namespace App\Http\Exports;


use App\Http\Traits\CSVExportTrait;
use App\Models\Incident;
use Carbon\Carbon;

class IncidentsExport implements ISimpleCsvExporter
{
    use CSVExportTrait;

    public function __construct(){}


    public function getHeadings(): array
    {
        return [
            'id',
            'Бейдж сотрудника',
            'Имя сотрудника',
            'Направление',
            'Тип',
            'Сообщение (wb-короб)',
            'Фото',
            'Дата',
        ];
    }

    public function getCount(array $arParams = []): int
    {
        $query = Incident::query();
        if(!empty($arParams['departmentId'])){
            $query->where('department_id', $arParams['departmentId']);
        }
        return $query->count('*') ?: 0;
    }

    public function query(int $chunkSize, int $offset, array $arParams = [])
    {
        $query = Incident::query();

        if(!empty($arParams['departmentId'])){
            $query->where('department_id', $arParams['departmentId']);
        }
        return $query->orderByDesc('id')->limit($chunkSize)->offset($offset)->get();
    }

    public function getRows($entities): array
    {
        $data = [];

        /** @var Incident $incident */
        foreach ($entities as $incident) {

            $row = [
                $incident->id,
                !empty($incident->worker_code) ? $incident->worker_code : '',
                !empty($incident->worker_name) ? $incident->worker_name : '',
                !empty($incident->department->name) ? $incident->department->name : '',
                !empty($incident->type) ? $incident->type : '',
                !empty($incident->message) ? $incident->message : '',
                is_array($incident->attachments) ? implode(', ', array_map(function ($v){return env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET') . '/' . $v;}, $incident->attachments)) : $incident->attachments,
                Carbon::parse($incident->created_at)->timezone('Europe/Moscow')->toDateTimeString(),
            ];

            $data[] = $row;
        }
        return $data;
    }
}
