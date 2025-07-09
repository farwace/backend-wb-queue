<?php

namespace App\Http\Controllers\Admin;

use App\Models\Incident;
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
class IncidentsCrudController extends CrudController
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
        CRUD::setModel(Incident::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/incidents');
        CRUD::setEntityNameStrings('инцидент', 'Инциденты');

    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {

        $this->crud->removeButton('update');
        $this->crud->removeButton('create');

        $backpackUser = backpack_user();
        if(!empty($backpackUser)){
            if(!$backpackUser->is_root){
                if(backpack_user()->departments){
                    $arIds = [];
                    foreach (backpack_user()->departments as $department){
                        $arIds[] = $department->id;
                    }
                    $this->crud->addClause('whereIn', 'department_id', $arIds);
                }
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
            'name' => 'worker_name',
            'type' => 'text',
            'label' => 'Сотрудник',
        ]);
        $this->crud->addColumn([
            'name' => 'worker_code',
            'type' => 'text',
            'label' => 'Идентификатор',
        ]);
        $this->crud->addColumn([
            'name' => 'message',
            'type' => 'text',
            'label' => 'Текст',
        ]);
        $this->crud->addColumn([
            'name' => 'type',
            'type' => 'text',
            'label' => 'Тип',
        ]);

        $this->crud->addColumn([
            'name' => 'attachments',
            'type' => 'view',
            'label' => 'Вложения',
            'view' => 'admin/columns/report-photo'
        ]);

    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
    }

    protected function setupCreateOperation(){

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
