<?php

namespace App\Http\Exports;


use App\Http\Traits\CSVExportTrait;
use App\Models\Report;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

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
            'Ошибка',
            'Другая причина (текст)',
            'ID (ШК) товара (фото)',
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
                $report->errorTypeLabel(),
                $report->error_note,
                $this->s3Url($report->product_barcode),
                $report->neighbor_barcode,
                $report->ttnTypeLabel(),
                $report->ttn_number,
                $this->s3Url($report->product_photo),
                $this->s3Url($report->office_id_photo),
            ];

            $data[] = $row;
        }
        return $data;
    }

    /**
     * Полный публичный URL файла в s3 (через конфиг диска — не ломается из-за config:cache).
     */
    private function s3Url(?string $path): string
    {
        return !empty($path) ? Storage::disk('s3')->url($path) : '';
    }
}
