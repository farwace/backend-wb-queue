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
        $report->name = $request->validated('name');
        $report->square = $request->validated('square');
        $report->error_type = $request->validated('error_type');
        $report->error_note = $request->validated('error_note');
        $report->neighbor_barcode = $request->validated('neighbor_barcode');
        $report->bk = $request->validated('bk');
        $report->department_id = $direction->id;

        // Фото обязательно только для ошибки «Нет ШК»
        /** @var UploadedFile|null $photo */
        $photo = $request->file('error_photo');
        if($photo){
            $path = $photo->store('reports/' . $direction_dode . '/' . now()->format('Y-m-d'), 's3');
            if(is_string($path) && $path !== ''){
                $report->error_photo = $path;
            }
            else{
                Log::warning('Не удалось сохранить фото отписки в s3', ['direction' => $direction_dode, 'worker' => $report->worker]);
            }
        }

        $report->save();

        return $this->success();
    }
}
