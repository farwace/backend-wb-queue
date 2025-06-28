<?php


namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class SubmitReplyRequest extends FormRequest{
    public function authorize(){
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'sometimes',
            'receiptNumber' => 'sometimes',
            'vehicleNumber' => 'sometimes',
            'gateNumbers' => 'sometimes',
            'videos' => 'sometimes | array',
            'videos.*' => 'required | file',
        ];
    }

    public function attributes(): array
    {
        return [
            'reason' => '«Причина разворота»',
            'receiptNumber' => '«Номер поступления»',
            'vehicleNumber' => '«Номер ТС»',
            'gateNumbers' => '«Номер ворот»',
            'videos' => '«Видео»',
        ];
    }
}
