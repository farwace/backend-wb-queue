<?php

namespace App\Http\Controllers\Reports;


use App\Http\Controllers\ReportsReplyController;
use App\Http\Requests\SubmitReportRequest;
use App\Http\Traits\RespondsWithHttpStatus;
use App\Models\Department;
use App\Models\Report;
use Illuminate\Http\UploadedFile;

class ApiController extends ReportsReplyController
{
    use RespondsWithHttpStatus;


    public function submit(string $direction_dode, SubmitReportRequest $request)
    {

        $direction = Department::query()->where('code', $direction_dode)->first();
        if(empty($direction)){
            return $this->failure('Не удалось определить направление');
        }

        $arData = $request->toArray();
        $report = new Report();
        $report->barcode = $arData['barcode'];
        $report->shortage = $arData['shortage'];
        $report->surplus = $arData['surplus'];
        $report->through = $arData['through'];
        $report->depersonalization_barcode = $arData['depersonalization_barcode'];
        $report->worker = $arData['worker'];
        $report->table = $arData['table'];
        $report->reason = $arData['reason'];
        $report->count = $arData['count'];
        $report->department_id = $direction->id;

        $arPath = [];
        /** @var UploadedFile $video */
        foreach ($arData['videos'] as $video) {
            $path = $video->store('reports/' . $direction_dode . '/' . now()->format('Y-m-d'), 's3');
            $arPath[] = $path;
        }
        $report->videos = $arPath;
        $report->save();

        return $this->success($arPath);
    }
}
