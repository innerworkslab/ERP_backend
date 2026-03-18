<?php

namespace Modules\Stakeholder\app\Http\Repositories;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseRepo
 * @package App\Http\Repositories
 */
abstract class BaseRepo
{
    /**
     * The model instance.
     *
     * @var Model
     */
    protected $model;

    /**
     * BaseRepo constructor.
     *
     * @param Model $model The Eloquent model instance.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get all records.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function all()
    {
        return $this->model->latest()->get();
    }

    /**
     * Get all records by orderBy.
     *
     * @param $col Column Name 'id'
     * @param $ordering Ordering 'asc'|'desc'
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function allOrderBy($col = 'id', $ordering = 'asc')
    {
        return $this->model->orderBy($col, $ordering)->latest()->get();
    }

    /**
     * Find a record by its primary key.
     *
     * @param mixed $id The primary key value.
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * Create a new record.
     *
     * @param array $attributes The data to create the record.
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(array $attributes)
    {
        return $this->model->create($attributes);
    }

    /**
     * Update a record.
     *
     * @param mixed $id The primary key value of the record to update.
     * @param array $attributes The data to update the record.
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function update($id, array $attributes)
    {
        $model = $this->find($id);

        if ($model) {
            $model->update($attributes);

            return $model;
        }

        return null;
    }

    /**
     * Delete a record.
     *
     * @param mixed $id The primary key value of the record to delete.
     * @return bool
     */
    public function delete($id)
    {
        $model = $this->find($id);

        if ($model) {
            $model->delete();

            return true;
        }

        return false;
    }

    /**
     * Get records where the given column has a given value.
     *
     * @param string $column The column to search.
     * @param mixed $value The value to search for.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function where($column, $value)
    {
        return $this->model->where($column, $value)->latest()->get();
    }

    /**
     * Get the first record matching the given multiple conditions.
     *
     * @param array $conditions The conditions to search for.
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function whereMultiple(array $conditions)
    {
        $query = $this->model;

        foreach ($conditions as $column => $value) {
            $query = $query->where($column, $value);
        }

        return $query->latest()->get();
    }

    /**
     * Get the first record matching the given column and value.
     *
     * @param string $column The column to search.
     * @param mixed $value The value to search for.
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function whereFirst($column, $value)
    {
        return $this->model->where($column, $value)->first();
    }

    /**
     * Get the first record matching the given multiple conditions.
     *
     * @param array $conditions The conditions to search for.
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function whereMultipleFirst(array $conditions)
    {
        $query = $this->model;

        foreach ($conditions as $column => $value) {
            $query = $query->where($column, $value);
        }

        return $query->first();
    }

    /**
     * Get records where the given column value is between two values.
     *
     * @param string $column The column to search.
     * @param mixed $startDate The start date.
     * @param mixed $endDate The end date.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function whereBetween($column, $startDate, $endDate)
    {
        return $this->model->whereBetween($column, [$startDate, $endDate])->get();
    }

    /**
     * Retrieve records where the specified key is in the given array of values.
     *
     * @param string $key The column name to search.
     * @param array $value An array of values to search for.
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function whereIn($key, $value)
    {
        return $this->model->whereIn($key, $value)->get();
    }

    /**
     * Delete records where the specified column matches the given value.
     *
     * @param string $column The column name to search.
     * @param mixed $value The value to search for.
     * @return bool True if records were deleted, false otherwise.
     */
    public function whereDelete($column, $value)
    {
        $model = $this->model->where($column, $value);

        if ($model) {
            $model->delete();

            return true;
        }
        return false;
    }

    /**
     * Check if a record exists with the given conditions.
     *
     * @param array $conditions The conditions to check.
     * @return bool
     */
    public function exists(array $conditions)
    {
        $query = $this->model;

        foreach ($conditions as $column => $value) {
            $query = $query->where($column, $value);
        }

        return $query->exists();
    }

    /**
     * Fill the model with an array of attributes.
     *
     * @param mixed $id The primary key value of the record to fill.
     * @param array $data The data to fill the record with.
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function fill($id, $data)
    {
        $model = $this->find($id);
        foreach ($data as $key => $value) {
            $model->$key = $value;
        }

        if ($model->save())
            return $model;
    }

    /**
     * Get paginated data with optional search, conditions, and relationships.
     *
     * @param int $perPage The number of items per page.
     * @param int $page The page number.
     * @param string $orderBy The column to order by.
     * @param array|null $searches The search criteria.
     * @param array $conditions The conditions to filter by.
     * @param array $orConditions The OR conditions to filter by.
     * @param array $with The relationships to eager load.
     * @return array
     */
    public function getDataWithPagination($perPage = 10, $page = 1, $orderBy = 'created_at', $searches = null, $conditions = [], $orConditions = [], $with = [], $whereHas = null, $status = null)
    {
        $query = $this->model->query();

        if (count($with) > 0) {
            $query->with($with);
        }

        if ($whereHas && is_array($whereHas)) {
            foreach ($whereHas as $relation => $constraint) {
                $query->whereHas($relation, $constraint);
            }
        }

        $offset = $perPage * ($page - 1);

        if ($orderBy) {
            $query->orderBy($orderBy, 'desc');
        }

        $query->limit($perPage);

        if (!empty($status)) {
            if ($status === 'inactive') {
                $query->where('status', 'inactive');
            } elseif ($status === 'active') {
                $query->where('status', 'active');
            } elseif ($status === 'closed') {
                $query->where('status', 'closed');
            } elseif ($status === 'open') {
                $query->where('status', 'open');
            } else {
                // status = 'all' → do not apply filter
            }
        }


        if ($searches) {
            $query->where(function ($q) use ($searches) {
                foreach ($searches as $key => $value) {
                    $q->orWhere($key, 'LIKE', "%$value%");
                }
            });
        }

        foreach ($conditions as $key => $condition) {
            $query->where($key, $condition);
        }

        foreach ($orConditions as $key => $condition) {
            $query->orWhere($key, $condition);
        }
        // Get total count
        $totalCount = $query->count();

        // Apply offset and limit
        if ($offset > 0) {
            $query->offset($offset);
        }

        // Calculate total pages
        $totalPages = ceil($totalCount / $perPage);

        $results = $query->latest()->get();

        return [
            'data' => $results,
            'meta' => [
                'total' => $totalCount,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
            ],
        ];
    }

    /**
     * Paginate the result set based on the given criteria.
     *
     * @param int $perPage The number of items per page.
     * @param array|null $searches An associative array of key-value pairs for searching.
     * @param array $wheres An associative array of key-value pairs for additional conditions.
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($perPage, $searches = null, $wheres = [])
    {
        foreach ($searches as $key => $value) {
            if ($value && $value != '') {
                $this->model = $this->model->where($key, 'LIKE', "%$value%");
            }
        }

        foreach ($wheres as $key => $value) {
            if (is_null($value)) {
                $this->model = $this->model->whereNull($key);
            } elseif (is_array($value)) {
                $this->model = $this->model->where($key, $value[0], $value[1]);
            } else {
                $this->model = $this->model->where($key, $value);
            }
        }
        $this->model = $this->model->latest();

        return $this->model->paginate($perPage);
    }
}