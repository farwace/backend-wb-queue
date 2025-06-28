<?php


namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

class SubmitReportRequest extends FormRequest{
    public function authorize(){
        return true;
    }

    public function rules(): array
    {
        return [
            'barcode' => 'required',
            'shortage' => 'required',
            'surplus' => 'required',
            'through' => 'required',
            'depersonalization_barcode' => 'required',
            'worker' => 'required',
            'table' => 'required',
            'reason' => 'required',
            'count' => 'required',
            'videos' => 'required | array',
            'videos.*' => 'required | file',
        ];
    }

    public function attributes(): array
    {
        return [
            'barcode' => '«ШК»',
            'shortage' => '«Недостача»',
            'surplus' => '«Излишек»',
            'through' => '«Через "ДА"»',
            'depersonalization_barcode' => '«Обезличка ШК»',
            'worker' => '«ID сотрудника»',
            'table' => '«№ Стола Приемки»',
            'reason' => '«Причина обезлички»',
            'count' => '«Количество»',
            'videos' => '«Обезличка видео»',
        ];
    }
}
