<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\Department;
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
class AdminsCrudController extends CrudController
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
        CRUD::setModel(Admin::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/admins');
        CRUD::setEntityNameStrings('админы', 'Админ');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {

        $this->crud->column('id')->type('number')->label('#');

        $this->crud->addColumn([
            'name' => 'name',
            'type' => 'text',
            'label' => 'Имя',
        ]);
        $this->crud->addColumn([
            'name' => 'email',
            'type' => 'text',
            'label' => 'E-mail',
        ]);

        $this->crud->addColumn([
            'name' => 'department.name',
            'type' => 'text',
            'label' => 'Направление'
        ]);
    }

    protected function setupShowOperation()
    {
       $this->setupListOperation();
    }

    protected function setupCreateOperation(){
        $this->crud->field('name')->type('text')->label('Имя')->attributes(['required' => 'true']);
        $this->crud->field('email')->type('text')->attributes(['required'=>'true'])->label('Email');
        $this->crud->addField([
            'name' => 'password',
            'type' => 'password',
            'label' => 'Пароль'
        ]);
        $this->crud->addField([
            'name' => 'department_id',
            'label' => 'Направление',
            'type' => 'select',
            'entity' => 'department',
            'attribute' => 'name',
            'model' => Department::class,
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
