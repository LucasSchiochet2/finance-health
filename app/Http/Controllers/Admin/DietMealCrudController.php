<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\DietMealRequest;
use App\Models\DietMeal;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class DietMealCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DietMealCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(DietMeal::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/diet-meal');
        CRUD::setEntityNameStrings('refeicao da dieta', 'refeicoes da dieta');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('date')->type('date')->label('Data');
        CRUD::column('status')->label('Status');
        CRUD::column('observation')->label('Observacao');
        CRUD::column('user_id')
            ->label('Usuario')
            ->type('select')
            ->entity('user')
            ->attribute('name')
            ->model(\App\Models\User::class)
            ->wrapper([
                'href' => function ($crud, $column, $entry, $related_key) {
                    return backpack_url('user/'.$related_key.'/show');
                },
            ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(DietMealRequest::class);

        CRUD::field('user_id')
            ->label('Usuario')
            ->type('select')
            ->entity('user')
            ->model(\App\Models\User::class)
            ->attribute('name');

        CRUD::field('date')
            ->type('date')
            ->label('Data')
            ->default(now()->toDateString());

        CRUD::field('status')
            ->type('select_from_array')
            ->label('Status')
            ->options(DietMeal::statusOptions());

        CRUD::field('observation')
            ->type('textarea')
            ->label('Observacao');
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
