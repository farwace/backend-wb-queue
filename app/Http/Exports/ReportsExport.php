<?php

namespace App\Http\Exports;


use App\Http\Traits\CSVExportTrait;
use App\Models\Report;
use Carbon\Carbon;

class ReportsExport implements ISimpleCsvExporter
{
    use CSVExportTrait;

    public function __construct(){}


    public function getHeadings(): array
    {
        return [
            'id',
            'Штрихкод',
            'Недостача',
            'Излишек',
            'Через Да',
            'Обезличка шк',
            'ID сотрудника',
            '№ Стола приемки',
            'Причина обезлички',
            'Количество',
            'Видео',
            'receipts', //?
            'Тип',
            'Дата',
            'Направление',
        ];
    }

    public function getCount(array $arParams = []): int
    {
        $query = Report::query();
        if(!empty($arParams['departmentId'])){
            $query->where('department_id', $arParams['departmentId']);
        }
        return $query->count('*') ?: 0;
    }

    public function query(int $chunkSize, int $offset, array $arParams = [])
    {
        $query = Report::query();

        if(!empty($arParams['departmentId'])){
            $query->where('department_id', $arParams['departmentId']);
        }
        return $query->orderByDesc('id')->limit($chunkSize)->offset($offset)->get();
    }

    public function getRows($entities): array
    {
        $data = [];

        /** @var Report $report */
        foreach ($entities as $report) {

            $row = [
                $report->id,
                $report->barcode,
                $report->shortage,
                $report->surplus,
                $report->through,
                $report->depersonalization_barcode,
                $report->worker,
                $report->table,
                $report->reason,
                $report->count,
                is_array($report->videos) ? implode(', ', array_map(function ($v){return env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET') . '/' . $v;}, $report->videos)) : $report->videos,
                $report->receipts,
                $report->type,
                Carbon::parse($report->created_at)->timezone('Europe/Moscow')->toDateTimeString(),
                $report->department ? $report->department->name : '',
            ];

            $data[] = $row;
        }
        return $data;
    }
}
