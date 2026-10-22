<?php

namespace App\Http\Controllers\Reports;


use App\Http\Controllers\ReportsReplyController;
use App\Http\Requests\SubmitReportRequest;
use App\Http\Traits\RespondsWithHttpStatus;
use App\Models\Department;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class ApiController extends ReportsReplyController
{
    use RespondsWithHttpStatus;


    public function submit(string $direction_dode, SubmitReportRequest $request): JsonResponse
    {

        $direction = Department::query()->where('code', $direction_dode)->first();
        if(empty($direction)){
            return $this->failure('Не удалось определить направление');
        }

        $report = new Report();
        $report->worker = $request->validated('worker');
        $report->error_type = $request->validated('error_type');
        $report->error_note = $request->validated('error_note');
        $report->neighbor_barcode = $request->validated('neighbor_barcode');
        $report->ttn_type = $request->validated('ttn_type');
        $report->ttn_number = $request->validated('ttn_number');
        $report->department_id = $direction->id;

        // Загрузка фото (product_barcode — «ID (ШК) товара», product_photo — «Фото товара»,
        // office_id_photo — «Фото ошибки с ТСД»). Обязательность зависит от типа ошибки
        // и проверяется в SubmitReportRequest.
        foreach (['product_barcode', 'product_photo', 'office_id_photo'] as $photoField) {
            /** @var UploadedFile|null $photo */
            $photo = $request->file($photoField);
            if ($photo) {
                $path = $photo->store('reports/' . $direction_dode . '/' . now()->format('Y-m-d'), 's3');
                if (is_string($path) && $path !== '') {
                    $report->$photoField = $path;
                } else {
                    Log::warning('Не удалось сохранить фото отписки в s3', ['field' => $photoField, 'direction' => $direction_dode, 'worker' => $report->worker]);
                }
            }
        }

        $report->save();

        return $this->success();
    }
}
