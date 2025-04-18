<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Models\AdminDepartment;
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
    use DeleteOperation;
    use ShowOperation;

    use CreateOperation{
        CreateOperation::store as parentStore;
    }
    use UpdateOperation{
        update as parentUpdate;
    }

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
            'name' => 'departments',
            'type' => 'model_function',
            'function_name' => 'getDepartmentsStrVal',
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

        $selectedDepartments = [];
        $entity = $this->crud->getCurrentEntry();
        if(!empty($entity)){
            $selectedDepartments = AdminDepartment::query()->where('admin_id', $entity->id)->pluck('department_id')->toArray();
        }
        CRUD::addField([
            'name'  => 'departments_custom',
            'label' => 'Направления',
            'type'  => 'select_from_array',
            'options' => Department::pluck('name', 'id')->toArray(),
            'allows_null' => false,
            'allows_multiple' => true,
            'default' => $selectedDepartments,
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


    public function store()
    {
        $parentStoreResult = $this->parentStore();
        $this->setDirections();
        return $parentStoreResult;
    }

    public function update(){
        $parentUpdateResult = $this->parentUpdate();
        $this->setDirections();
        return $parentUpdateResult;
    }

    public function setDirections()
    {
        if(!empty($this->data['entry']->id)){

            if(isset(request()->post()['departments_custom'])){
                $arDepartmentsIds = request()->post()['departments_custom'];
                AdminDepartment::where('admin_id', $this->data['entry']->id)->delete();
                if(!empty($arDepartmentsIds)){
                    foreach ($arDepartmentsIds as $departmentId) {
                        AdminDepartment::query()->insert([
                            'admin_id' => $this->data['entry']->id,
                            'department_id' => (int)$departmentId
                        ]);
                    }
                }
            }
        }
    }
}
