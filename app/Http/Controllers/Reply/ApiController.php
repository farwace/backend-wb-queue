<?php

namespace App\Http\Controllers\Reply;

use App\Http\Controllers\ReportsReplyController;
use App\Http\Requests\SubmitReplyRequest;
use App\Http\Traits\RespondsWithHttpStatus;
use App\Models\Department;
use App\Models\Reply;
use Illuminate\Http\UploadedFile;


class ApiController extends ReportsReplyController
{
    use RespondsWithHttpStatus;

   public function submit(string $direction_dode, SubmitReplyRequest $request)
    {

        $direction = Department::query()->where('code', $direction_dode)->first();
        if(empty($direction)){
            return $this->failure('Не удалось определить направление');
        }

        $arData = $request->toArray();
        $reply = new Reply();
        $reply->reason = $arData['reason'];
        $reply->department_id = $direction->id;
        $reply->gateNumbers = $arData['gateNumbers'];
        $reply->receiptNumber = $arData['receiptNumber'];
        $reply->vehicleNumber = $arData['vehicleNumber'];

        $arPath = [];
        if(!empty($arData['videos'])){
            /** @var UploadedFile $video */
            foreach ($arData['videos'] as $video) {
                $path = $video->store('reply/' . $direction_dode . '/' . now()->format('Y-m-d'), 's3');
                $arPath[] = $path;
            }
        }
        $reply->videos = $arPath;
        $reply->save();

        return $this->success();
    }

}
