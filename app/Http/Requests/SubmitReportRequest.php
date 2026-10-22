<?php


namespace App\Http\Requests;


use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;

class SubmitReportRequest extends FormRequest{
    public function authorize(){
        return true;
    }

    public function rules(): array
    {
        return [
            'worker' => 'required|string',
            'error_type' => 'required|in:' . implode(',', array_keys(Report::ERROR_TYPES)),

            // «Другая причина» — обязательен текст
            'error_note' => 'required_if:error_type,other|string',

            // ID (ШК) товара — фото, для «Товар не в заказе» и «Неверный офис»
            'product_barcode' => 'required_if:error_type,not_in_order,office_error|nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',

            // Соседний ID (ШК) с поступления — для «Без ID (ШК)»
            'neighbor_barcode' => 'required_if:error_type,no_barcode|nullable|string',

            // ТТН + номер — для «Без ID (ШК)»
            'ttn_type' => 'required_if:error_type,no_barcode|nullable|in:' . implode(',', array_keys(Report::TTN_TYPES)),
            'ttn_number' => 'required_if:error_type,no_barcode|nullable|regex:/^\d+$/',

            // Фото товара — для «Товар не в заказе», «Неверный офис», «Без ID (ШК)»
            'product_photo' => 'required_if:error_type,not_in_order,office_error,no_barcode|nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',

            // Фото «Ошибка с ТСД» — только для «Неверный офис»
            'office_id_photo' => 'required_if:error_type,office_error|nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
        ];
    }

    public function attributes(): array
    {
        return [
            'worker' => '«ID сотрудника»',
            'error_type' => '«Ошибка»',
            'error_note' => '«Другая причина»',
            'product_barcode' => '«ID (ШК) товара»',
            'neighbor_barcode' => '«Соседний ID (ШК) с поступления»',
            'ttn_type' => '«ТТН»',
            'ttn_number' => '«Номер ТТН»',
            'product_photo' => '«Фото товара»',
            'office_id_photo' => '«Фото ошибки с ТСД»',
        ];
    }
}
