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

            // ID (ШК) товара №1 / №2 — фото, для «Два ШК»
            'product_barcode_1' => 'required_if:error_type,two_barcodes|nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',
            'product_barcode_2' => 'required_if:error_type,two_barcodes|nullable|file|image|mimes:jpg,jpeg,png,webp|max:10240',

            // Коды QR, распознанные на фронте с фото ШК (сервер сверяет свою копию в ApiController)
            'product_barcode_value' => 'nullable|string|max:255',
            'product_barcode_1_value' => 'nullable|string|max:255',
            'product_barcode_2_value' => 'nullable|string|max:255',

            // Соседний ID (ШК) с поступления — для «Без ID (ШК)»
            'neighbor_barcode' => 'required_if:error_type,no_barcode|nullable|string',

            // ТТН + номер — для «Без ID (ШК)», необязательные
            'ttn_type' => 'nullable|in:' . implode(',', array_keys(Report::TTN_TYPES)),
            'ttn_number' => 'nullable|regex:/^\d+$/',

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
            'product_barcode_1' => '«ID (ШК) товара №1»',
            'product_barcode_2' => '«ID (ШК) товара №2»',
            'product_barcode_value' => '«QR-код с фото ID (ШК) товара»',
            'product_barcode_1_value' => '«QR-код с фото ID (ШК) товара №1»',
            'product_barcode_2_value' => '«QR-код с фото ID (ШК) товара №2»',
            'neighbor_barcode' => '«Соседний ID (ШК) с поступления»',
            'ttn_type' => '«ТТН»',
            'ttn_number' => '«Номер ТТН»',
            'product_photo' => '«Фото товара»',
            'office_id_photo' => '«Фото ошибки с ТСД»',
        ];
    }
}
