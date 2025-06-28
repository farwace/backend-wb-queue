<?php

namespace App\Http\Controllers\Admin;

use App\Models\Department;
use App\Models\Reply;
use App\Models\Report;
use App\Models\Table;
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
class RepliesCrudController extends CrudController
{
    use ListOperation;
    use CreateOperation;
    use UpdateOperation;
    use DeleteOperation;
    use ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Reply::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/replies');
        CRUD::setEntityNameStrings('отписку грузчика', 'Отписки грузчиков');

    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {

        $backpackUser = backpack_user();
        if(!empty($backpackUser)){
            if(!$backpackUser->is_root){

            }
        }

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
            'name' => 'receiptNumber',
            'type' => 'text',
            'label' => 'Номер поступления',
        ]);
        $this->crud->addColumn([
            'name' => 'vehicleNumber',
            'type' => 'text',
            'label' => 'Номер ТС',
        ]);
        $this->crud->addColumn([
            'name' => 'reason',
            'type' => 'text',
            'label' => 'Причина разворота',
        ]);
        $this->crud->addColumn([
            'name' => 'gateNumbers',
            'type' => 'text',
            'label' => 'Номер ворот',
        ]);

        $this->crud->addColumn([
            'name' => 'videos',
            'type' => 'view',
            'label' => 'Обезличка видео',
            'view' => 'admin/columns/report-video'
        ]);

    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
    }

    protected function setupCreateOperation(){

        $this->crud->addField([
            'name' => 'receiptNumber',
            'type' => 'text',
            'label' => 'Номер поступления',
        ]);
        $this->crud->addField([
            'name' => 'vehicleNumber',
            'type' => 'text',
            'label' => 'Номер ТС',
        ]);
        $this->crud->addField([
            'name' => 'reason',
            'type' => 'text',
            'label' => 'Причина разворота',
        ]);
        $this->crud->addField([
            'name' => 'gateNumbers',
            'type' => 'text',
            'label' => 'Номер ворот',
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
