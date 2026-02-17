<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\WorkoutRequest;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class WorkoutCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class WorkoutCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Workout::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/workout');
        CRUD::setEntityNameStrings('workout', 'workouts');
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('name')->label('Treino');
        CRUD::column('description')->label('Descrição');
        CRUD::column('default_sets')->label('Séries Padrão');
        CRUD::column('default_reps')->label('Repetições Padrão');
        CRUD::column('observation')->label('Observações');
        CRUD::column('user_id')
            ->label('Usuário')
            ->type('select')
            ->entity('user')
            ->attribute('name')
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
        CRUD::setValidation(WorkoutRequest::class);

        CRUD::field('name')->label('Nome do Treino');
        CRUD::field('description')->type('textarea')->label('Descrição')->hint('Ex: Dia de Pernas A');
        CRUD::field('observation')->type('textarea')->label('Observações')->hint('Ex: Descanso de 60s entre séries');

        CRUD::field('default_sets')->type('number')->label('Séries Padrão');
        CRUD::field('default_reps')->type('number')->label('Repetições Padrão');

        CRUD::field('user_id')
            ->label('Usuário Vinculado')
            ->type('select')
            ->entity('user')
            ->attribute('name')
            ->model(\App\Models\User::class)
            ->nullable()
            ->hint('Deixe vazio para ser um treino padrão do sistema');

        CRUD::field('exercises')
            ->label('Exercícios')
            ->type('select2_multiple')
            ->entity('exercises')
            ->attribute('name')
            ->pivot(true);
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
