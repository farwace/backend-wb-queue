<?php

namespace App\Http\Controllers\Reply;

use App\Http\Controllers\ReportsReplyController;
use App\Http\Traits\RespondsWithHttpStatus;


class ApiController extends ReportsReplyController
{
    use RespondsWithHttpStatus;

    public function submit()
    {
        return $this->success([], 'aaaa');
    }

}
