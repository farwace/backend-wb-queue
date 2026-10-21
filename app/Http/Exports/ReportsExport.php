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
            'Дата',
            'Направление',
            'ID сотрудника',
            'Имя',
            'Квадрат сотрудника',
            'Ошибка',
            'Другая причина (текст)',
            'ID (ШК) товара',
            'Соседний ID (ШК) с поступления',
            'ТТН',
            'Номер ТТН',
            'Фото товара',
            'Фото ошибки с ТСД',
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
                Carbon::parse($report->created_at)->timezone('Europe/Moscow')->toDateTimeString(),
                $report->department ? $report->department->name : '',
                $report->worker,
                $report->name,
                $report->square,
                $report->errorTypeLabel(),
                $report->error_note,
                $report->product_barcode,
                $report->neighbor_barcode,
                $report->ttnTypeLabel(),
                $report->ttn_number,
                !empty($report->product_photo) ? (env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET') . '/' . $report->product_photo) : '',
                !empty($report->office_id_photo) ? (env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET') . '/' . $report->office_id_photo) : '',
            ];

            $data[] = $row;
        }
        return $data;
    }
}
