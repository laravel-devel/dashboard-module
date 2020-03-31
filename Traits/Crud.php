<?php

namespace Modules\DevelDashboard\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait Crud
{
    /**
     * A model class to perform CRUD opeations on.
     *
     * @var string
     */
    protected $modelClass;

    /**
     * Form Request class.
     *
     * @var string
     */
    protected $requestClass;

    /**
     * List of model fields to include in the datatable.
     *
     * @var array
     */
    protected $datatableFields = [];

    /**
     * List of datatable row actions.
     *
     * @var array
     */
    protected $datatableActions = [];

    /**
     * List of datatable filters.
     *
     * @var array
     */
    protected $datatableFilters = [];

    /**
     * List of required CRUD permissions.
     *
     * @var array
     */
    protected $datatablePermissions = [];

    /**
     * List of form fields to include into the create/edit forms.
     *
     * @var array
     */
    protected $formFields = [];

    /**
     * Datatable - items per page
     *
     * @var integer
     */
    protected $itemsPerPage = 20;

    /**
     * Set the model class
     *
     * @param string $class
     * @return void
     */
    protected function setModel(string $class): void
    {
        $this->modelClass = $class;
    }

    /**
     * Set the form Request class
     *
     * @param string $class
     * @return void
     */
    protected function setRequest(string $class): void
    {
        $this->requestClass = $class;
    }

    /**
     * Set datatable fields to be displayed.
     *
     * @param array $fields
     * @param array $actions
     * @param array $filters
     * @return void
     */
    protected function setDatatable(array $fields, array $actions = [], array $filters = []): void
    {
        $this->datatableFields = $fields;
        $this->setActions($actions);
        $this->datatableFilters = $filters;
    }

    /**
     * Set form fields to be included into the form.
     *
     * @param array $fields
     * @return void
     */
    protected function setForm(array $fields): void
    {
        foreach ($fields as $tab => $data) {
            for ($i = 0; $i < count($data); $i++) {
                if (!isset($fields[$tab][$i]['attrs'])) {
                    $fields[$tab][$i]['attrs'] = [];
                }
            }
        }

        $this->formFields = $fields;
    }

    /**
     * Set datatable actions
     *
     * @param array $actions
     * @return void
     */
    protected function setActions(array $actions): void
    {
        foreach ($actions as $action => $values) {
            if (is_numeric(array_keys($values)[0])) {
                $this->datatableActions[$action] = [
                    'url' => route($values[0], $values[1] ?? null),
                ];

                $route = $values[0];
            } else {
                $route = $values['url'][0];

                $values['url'] = route($values['url'][0], $values['url'][1] ?? null);

                $this->datatableActions[$action] = $values;
            }

            // Set permissions for each route
            $this->datatablePermissions[$action] =
                \Route::getRoutes()->getByName($route)->getAction()['permissions'] ?? [];
        }
    }

    /**
     * Return the model class
     *
     * @return string
     */
    protected function model(): string
    {
        return $this->modelClass;
    }

    /**
     * Return the datatable fields list
     *
     * @return array
     */
    protected function datatable(): array
    {
        $model = $this->model();

        $this->datatableFields['_primary_key'] = (new $model)->getRouteKeyName();

        return $this->datatableFields;
    }

    /**
     * Return the datatable fields list
     *
     * @return array
     */
    protected function actions(): array
    {
        return $this->datatableActions;
    }

    /**
     * Return the datatable filters list
     *
     * @return array
     */
    protected function filters(): array
    {
        return $this->datatableFilters;
    }

    /**
     * Return the datatable permissions
     *
     * @return array
     */
    protected function permissions(): array
    {
        return $this->datatablePermissions;
    }

    /**
     * Return the form fields list
     *
     * @return array
     */
    protected function form(): array
    {
        return $this->formFields;
    }

    /**
     * API. Return a resource collection.
     *
     * @return Response
     */
    public function get(Request $request)
    {
        $model = $this->model();
        $table = (new $model)->getTable();

        $query = $this->model()::select("{$table}.*")
            ->sort($request->sort)
            ->filter($request)
            ->search($request->search);

        $query = $this->eagerLoadRelationships($query);

        return response()->json($query->paginate($this->itemsPerPage));
    }

    /**
     * Show the specified resource.
     *
     * @param mixed $id
     * @return Response
     */
    public function show($id)
    {
        //
    }

    /**
     * API. Store a newly created resource.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $item = $this->storeOrUpdate($request);

        return response()->json($item, 201);
    }

    /**
     * Update the specified resource.
     *
     * @param Request $request
     * @param mixed $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        if (($can = $this->canBeEdited($request, $id)) !== true) {
            return response()->json([
                'message' => $can,
            ], 409);
        }

        $item = $this->model()::findOrFail($id);

        $item = $this->storeOrUpdate($request, $item);

        return response()->json($item, 200);
    }

    /**
     * Delete the specified resource.
     *
     * @param mixed $id
     * @return Response
     */
    public function destroy(Request $request, $id)
    {
        if (($can = $this->canBeDeleted($request, $id)) !== true) {
            return response()->json([
                'message' => $can,
            ], 409);
        }

        $model = new $this->modelClass;

        $object = $this->model()::where($model->getRouteKeyName(), $id)->first();

        if (!$object) {
            return response()->json([
                'message' => 'Item with provided id was not found!',
            ], 404);
        }

        $object->delete();

        return response()->json([]);
    }

    /**
     * Determine whether an item can be edited.
     *
     * @param Request $request
     * @param mixed $id
     * @return mixed
     */
    protected function canBeEdited($request, $id)
    {
        return true;
    }

    /**
     * Determine whether an item can be deleted.
     *
     * @param Request $request
     * @param mixed $id
     * @return mixed
     */
    protected function canBeDeleted($request, $id)
    {
        return true;
    }

    /**
     * Store or update an item.
     *
     * @param Request $request
     * @param mixed $item
     * @return mixed
     */
    protected function storeOrUpdate($request, $item = null)
    {
        // Validation
        if ($this->requestClass) {
            app($this->requestClass);
        }

        $values = [];
        $model = $this->model();
        $model = new $model;

        $table = $model->getTable();
        $columns = Schema::getColumnListing($table);

        foreach ($columns as $field) {
            $columnType = DB::getSchemaBuilder()
                ->getColumnType($table, $field);

            if ($columnType === 'boolean') {
                $values[$field] = $request->has($field);
            } else {
                if ($request->has($field)) {
                    $values[$field] = $request->get($field);
                }
            }
        }

        $values = $this->alterValues($request, $values, $item);

        if ($item) {
            $item->update($values);
        } else {
            $item = $this->model()::create($values);
        }

        // Update the relationships
        foreach ($item->getRelationships() as $name => $attrs) {
            if (!method_exists($item, $name) || !$request->has($name)) {
                continue;
            }

            $value = $request->get($name) ?? ($item ? null : []);

            // If the value hasn't changed
            if ($value === null) {
                continue;
            }

            switch ($attrs['type']) {
                case 'BelongsToMany':
                    // array_filters removes the null values
                    $item->{$name}()->sync(array_filter($value));

                    break;
                    // TODO: missing relationships (you can get the locale/foreign
                    // keys via $attrs['relation'] or maybe I can directly set the
                    // relations via Eloquent?)
                    // - HasMany (probably exact same code as for 'BelongsToMany')
                    // - BelongsToOne
                    // - HasOne
            }
        }

        $item = $this->afterStoreOrUpdate($request, $item);

        return $item;
    }

    /**
     * Perform actions on the model after storing or updating it.
     *
     * @param Request $request
     * @param mixed $item
     * @return mixed
     */
    protected function afterStoreOrUpdate($request, $item)
    {
        return $item;
    }

    /**
     * Alter the values before storing or updating an item.
     *
     * @param Request $request
     * @param array $values
     * @param mixed $item
     * @return array
     */
    protected function alterValues($request, array $values, $item = null): array
    {
        return $values;
    }

    /**
     * Set the datatable number of items per page.
     *
     * @param integer $number
     * @return void
     */
    protected function setItemsPerPage(int $number): void
    {
        if ($number < 1) {
            $number = 1;
        }

        $this->itemsPerPage = $number;
    }

    /**
     * Eager load relationships for the datatable columns.
     *
     * @param Illuminate\Database\Eloquent\Builder $query
     * @return Illuminate\Database\Eloquent\Builder
     */
    protected function eagerLoadRelationships(Builder $query): Builder
    {
        // If any of the datatable columns contain relationships
        foreach ($this->datatable() as $key => $attrs) {
            if (strpos($key, '.') !== false) {
                $relation = explode('.', $key)[0];

                // Sometimes a relationship could not be found because the
                // string is in a wrong case (camel instead of snake or
                // vise-versa)
                if (!method_exists($this->model(), $relation)) {
                    $relation = strpos($relation, '_') !== false
                        ? \Str::camel($relation)
                        : \Str::snake($relation);
                }

                // If the relationship still doesn't exist - let it throw an 
                // exception

                $query->with($relation);
            }
        }

        return $query;
    }
}
