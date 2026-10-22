<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Report;
use App\Models\Worker;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * @package App\Http\Controllers\Admin
 * @property-read CrudPanel $crud
 */
class ReportsCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ShowOperation;

    /**
     * AJAX-переключение отметки «Обработано» из списка.
     */
    public function toggleProcessed(int $id)
    {
        $report = Report::findOrFail($id);
        $report->processed = !$report->processed;
        $report->save();

        return response()->json(['success' => true, 'processed' => $report->processed]);
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Report::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/reports');
        CRUD::setEntityNameStrings('отписку', 'Отписки');

    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        $this->crud->addButton('top', 'export', 'model_function', 'exportButtonContent');

        $this->crud->column('id')->type('number')->label('#ID');

        $this->crud->addColumn([
            'name' => 'department.name',
            'type' => 'text',
            'label' => 'Направление',
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('department', function ($query) use ($column, $searchTerm) {
                    $query->where('name', 'like', '%' . $searchTerm . '%')->orWhere('code', 'like', '%' . $searchTerm . '%');
                });
            }
        ]);
        $this->crud->addColumn([
            'name' => 'created_at',
            'type' => 'closure',
            'label' => 'Дата/время',
            'function' => function ($entry) {
                return $entry->created_at->addHours(3)->locale('ru')
                    ->isoFormat('D MMM YYYY, HH:mm');
            }
        ]);

        $this->crud->addColumn([
            'name' => 'worker',
            'type' => 'text',
            'label' => 'ID сотрудника',
        ]);
        $this->crud->addColumn([
            'name' => 'processed',
            'type' => 'closure',
            'label' => 'Обработано',
            'escaped' => false,
            'function' => function ($entry) {
                $url = e(route('admin.reports.toggle-processed', $entry->id));
                $icon = $entry->processed ? 'la-check-circle text-success' : 'la-circle text-muted';
                $title = $entry->processed ? 'Обработано — нажать, чтобы снять отметку' : 'Отметить как обработанную';
                return '<button type="button" class="btn btn-sm btn-link p-0 border-0 report-processed-toggle" data-url="' . $url . '" title="' . $title . '">'
                    . '<i class="la ' . $icon . ' fs-4"></i></button>';
            },
        ]);
        // Обработчик клика по чекбоксу «Обработано» (AJAX-переключение без выхода в edit).
        // Скрипт вставляется один раз на страницу (static-флаг внутри closure).
        $this->crud->addColumn([
            'name' => 'processed_toggle_script',
            'type' => 'closure',
            'label' => ' ',
            'escaped' => false,
            'function' => function ($entry) {
                static $rendered = false;
                if ($rendered) {
                    return '';
                }
                $rendered = true;
                return <<<'HTML'
<script>
(function () {
    if (window.__reportProcessedToggle) { return; }
    window.__reportProcessedToggle = true;
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.report-processed-toggle');
        if (!btn) { return; }
        e.preventDefault();
        var meta = document.querySelector('meta[name=csrf-token]');
        fetch(btn.dataset.url, {
            method: 'POST',
            headers: {
                'X-CSRF-Token': meta ? meta.content : '',
                'Accept': 'application/json'
            }
        }).then(function (r) { if (r.ok) { location.reload(); } });
    });
})();
</script>
HTML;
            },
        ]);
        $this->crud->addColumn([
            'name' => 'error_type',
            'type' => 'closure',
            'label' => 'Ошибка',
            'function' => function ($entry) {
                return e($entry->errorTypeLabel());
            }
        ]);
        $this->crud->addColumn([
            'name' => 'qr_mismatch',
            'type' => 'closure',
            'label' => 'Расхождение ШК',
            'escaped' => false,
            'function' => fn ($entry) => $entry->qrMismatchHtml(),
        ]);
        $this->crud->addColumn([
            'name' => 'error_note',
            'type' => 'text',
            'label' => 'Другая причина (текст)',
        ]);
        $this->crud->addColumn([
            'name' => 'product_barcode',
            'type' => 'closure',
            'label' => 'ID (ШК) товара',
            'escaped' => false,
            'function' => fn ($entry) => $entry->productBarcodePhotoHtml(),
        ]);
        $this->crud->addColumn([
            'name' => 'product_barcode_1',
            'type' => 'closure',
            'label' => 'ID (ШК) товара №1',
            'escaped' => false,
            'function' => fn ($entry) => $entry->productBarcode1PhotoHtml(),
        ]);
        $this->crud->addColumn([
            'name' => 'product_barcode_2',
            'type' => 'closure',
            'label' => 'ID (ШК) товара №2',
            'escaped' => false,
            'function' => fn ($entry) => $entry->productBarcode2PhotoHtml(),
        ]);
        $this->crud->addColumn([
            'name' => 'neighbor_barcode',
            'type' => 'text',
            'label' => 'Соседний ID (ШК) с поступления',
        ]);
        $this->crud->addColumn([
            'name' => 'ttn_type',
            'type' => 'closure',
            'label' => 'ТТН',
            'function' => function ($entry) {
                return e($entry->ttnTypeLabel());
            }
        ]);
        $this->crud->addColumn([
            'name' => 'ttn_number',
            'type' => 'text',
            'label' => 'Номер ТТН',
        ]);
        $this->crud->addColumn([
            'name' => 'product_photo',
            'type' => 'closure',
            'label' => 'Фото товара',
            'escaped' => false,
            'function' => fn ($entry) => $entry->productPhotoHtml(),
        ]);
        $this->crud->addColumn([
            'name' => 'office_id_photo',
            'type' => 'closure',
            'label' => 'Фото ошибки с ТСД',
            'escaped' => false,
            'function' => fn ($entry) => $entry->officeIdPhotoHtml(),
        ]);
    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
        // Детальная страница: показываем, что сервер распознал на фото (в т.ч. при расхождении)
        $this->crud->addColumn([
            'name' => 'qr_server_values',
            'type' => 'closure',
            'label' => 'ШК, распознанные сервером',
            'function' => function ($entry) {
                $server = json_decode((string)$entry->qr_server_values, true) ?: [];
                if ($server === []) {
                    return e('— (серверу не удалось распознать QR)');
                }
                $lines = [];
                foreach ($server as $field => $value) {
                    $lines[] = e($field) . ': ' . e($value);
                }
                return implode("\n", $lines);
            },
        ]);
    }

    protected function setupCreateOperation(){
        $this->crud->setValidationFromArray([
            'worker' => 'required|string',
            'error_type' => 'required|in:' . implode(',', array_keys(Report::ERROR_TYPES)),
            'error_note' => 'required_if:error_type,other|string',
            'product_barcode' => 'required_if:error_type,not_in_order,office_error|nullable|image',
            'product_barcode_1' => 'required_if:error_type,two_barcodes|nullable|image',
            'product_barcode_2' => 'required_if:error_type,two_barcodes|nullable|image',
            'product_barcode_value' => 'nullable|string|max:255',
            'product_barcode_1_value' => 'nullable|string|max:255',
            'product_barcode_2_value' => 'nullable|string|max:255',
            'processed' => 'boolean',
            'qr_mismatch' => 'boolean',
            'neighbor_barcode' => 'required_if:error_type,no_barcode|nullable|string',
            'ttn_type' => 'nullable|in:' . implode(',', array_keys(Report::TTN_TYPES)),
            'ttn_number' => 'nullable|regex:/^\d+$/',
            'product_photo' => 'required_if:error_type,not_in_order,office_error,no_barcode|nullable|image',
            'office_id_photo' => 'required_if:error_type,office_error|nullable|image',
        ]);

        $this->crud->addField([
            'name' => 'department_id',
            'type' => 'select',
            'label' => 'Направление',
            'entity' => 'department',
            'attribute' => 'name',
            'model' => Department::class,
            'placeholder' => '— выберите направление —',
        ]);
        $this->crud->addField([
            'name' => 'worker',
            'type' => 'text',
            'label' => 'ID сотрудника',
        ]);
        $this->crud->addField([
            'name' => 'error_type',
            'type' => 'select_from_array',
            'label' => 'Ошибка',
            'options' => Report::ERROR_TYPES,
            'placeholder' => '— выберите ошибку —',
        ]);
        $this->crud->addField([
            'name' => 'processed',
            'type' => 'checkbox',
            'label' => 'Обработано',
            'hint' => 'Отметка админа: отписка уже обработана',
        ]);
        $this->crud->addField([
            'name' => 'qr_mismatch',
            'type' => 'checkbox',
            'label' => 'Расхождение ШК',
            'hint' => 'Сервер распознал QR-код, отличающийся от клиентского (ставится автоматически при отправке)',
        ]);
        $this->crud->addField([
            'name' => 'error_note',
            'type' => 'text',
            'label' => 'Другая причина (текст)',
            'hint' => 'Для «Другая причина»',
        ]);
        $this->crud->addField([
            'name' => 'product_barcode',
            'type' => 'upload',
            'label' => 'ID (ШК) товара (фото)',
            'disk' => 's3',
            'prefix' => 'reports-photos',
            'hint' => 'Для «Товар не в заказе» / «Неверный офис»',
        ]);
        $this->crud->addField([
            'name' => 'product_barcode_1',
            'type' => 'upload',
            'label' => 'ID (ШК) товара №1 (фото)',
            'disk' => 's3',
            'prefix' => 'reports-photos',
            'hint' => 'Для «Два ШК»',
        ]);
        $this->crud->addField([
            'name' => 'product_barcode_2',
            'type' => 'upload',
            'label' => 'ID (ШК) товара №2 (фото)',
            'disk' => 's3',
            'prefix' => 'reports-photos',
            'hint' => 'Для «Два ШК»',
        ]);
        $this->crud->addField([
            'name' => 'product_barcode_value',
            'type' => 'text',
            'label' => 'QR-код с фото «ID (ШК) товара»',
        ]);
        $this->crud->addField([
            'name' => 'product_barcode_1_value',
            'type' => 'text',
            'label' => 'QR-код с фото «ID (ШК) товара №1»',
            'hint' => 'Для «Два ШК»',
        ]);
        $this->crud->addField([
            'name' => 'product_barcode_2_value',
            'type' => 'text',
            'label' => 'QR-код с фото «ID (ШК) товара №2»',
            'hint' => 'Для «Два ШК»',
        ]);
        $this->crud->addField([
            'name' => 'neighbor_barcode',
            'type' => 'text',
            'label' => 'Соседний ID (ШК) с поступления',
            'hint' => 'Для «Без ID (ШК)»',
        ]);
        $this->crud->addField([
            'name' => 'ttn_type',
            'type' => 'select_from_array',
            'label' => 'ТТН',
            'options' => Report::TTN_TYPES,
            'placeholder' => '— выберите ТТН —',
            'hint' => 'Для «Без ID (ШК)», необязательно',
        ]);
        $this->crud->addField([
            'name' => 'ttn_number',
            'type' => 'text',
            'label' => 'Номер ТТН',
            'hint' => 'Только цифры, необязательно',
        ]);
        $this->crud->addField([
            'name' => 'product_photo',
            'type' => 'upload',
            'label' => 'Фото товара',
            'disk' => 's3',
            'prefix' => 'reports-photos',
        ]);
        $this->crud->addField([
            'name' => 'office_id_photo',
            'type' => 'upload',
            'label' => 'Фото ошибки с ТСД',
            'disk' => 's3',
            'prefix' => 'reports-photos',
            'hint' => 'Для «Неверный офис»',
        ]);

        // Условный показ полей в зависимости от выбранного типа ошибки
        // (инлайн через custom_html — в Backpack 6.8 метода CrudPanel::addScript нет)
        $this->crud->addField([
            'name' => 'report_form_toggle',
            'type' => 'custom_html',
            'value' => <<<'HTML'
<script>
(function () {
    // элемент => список типов ошибки, при которых поле показывается
    var rules = {
        error_note: ['other'],
        product_barcode: ['not_in_order', 'office_error'],
        product_barcode_1: ['two_barcodes'],
        product_barcode_2: ['two_barcodes'],
        neighbor_barcode: ['no_barcode'],
        ttn_type: ['no_barcode'],
        ttn_number: ['no_barcode'],
        product_photo: ['not_in_order', 'office_error', 'no_barcode'],
        office_id_photo: ['office_error']
    };
    function setup() {
        var select = document.getElementById('error_type');
        if (!select) { return; }
        var groups = {};
        Object.keys(rules).forEach(function (key) {
            var el = document.getElementById(key);
            groups[key] = el ? el.closest('.form-group') : null;
        });
        function toggle() {
            var v = select.value;
            Object.keys(rules).forEach(function (key) {
                if (groups[key]) {
                    groups[key].style.display = rules[key].indexOf(v) !== -1 ? '' : 'none';
                }
            });
        }
        select.addEventListener('change', toggle);
        toggle();
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', setup);
    } else { setup(); }
})();
</script>
HTML,
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }
}
