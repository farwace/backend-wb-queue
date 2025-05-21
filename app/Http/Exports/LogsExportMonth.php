<?php

namespace App\Http\Exports;


use App\Http\Traits\CSVExportTrait;
use App\Models\QueueLog;
use Carbon\Carbon;

class LogsExportMonth implements ISimpleCsvExporter
{
    use CSVExportTrait;

    public function __construct(){}


    public function getHeadings(): array
    {
        return [
            'id',
            'Бейдж сотрудника',
            'Имя сотрудника',
            'Стол',
            'Статус',
            'Комментарий',
            'Дата',
            'Направление',
        ];
    }

    public function getCount(array $arParams = []): int
    {
        $query = QueueLog::query()->where('created_at', '>', Carbon::now()->subDays(30));
        if(!empty($arParams['departmentId'])){
            $query->where('department_id', $arParams['departmentId']);
        }
        return $query->count('*') ?: 0;
    }

    public function query(int $chunkSize, int $offset, array $arParams = [])
    {
        $query = QueueLog::query()->where('created_at', '>', Carbon::now()->subDays(30));

        if(!empty($arParams['departmentId'])){
            $query->where('department_id', $arParams['departmentId']);
        }
        return $query->orderByDesc('id')->limit($chunkSize)->offset($offset)->get();
    }

    public function getRows($entities): array
    {
        $data = [];

        /** @var QueueLog $rule */
        foreach ($entities as $rule) {

            $row = [
                $rule->id,
                $rule->worker_badge,
                $rule->worker_name,
                $rule->table,
                $rule->status,
                $rule->message,
                Carbon::parse($rule->created_at)->timezone('Europe/Moscow')->toDateTimeString(),
                $rule->department->name,
            ];

            $data[] = $row;
        }
        return $data;
    }
}
