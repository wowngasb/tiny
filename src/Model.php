<?php
/**
 * Created by PhpStorm.
 * User: kongl
 * Date: 2018/1/24 0024
 * Time: 19:46
 */

namespace Tiny;

use Closure;
use Illuminate\Database\Eloquent\Model as _Model;
use stdClass;
use Tiny\Plugin\DbHelper;
use Tiny\Traits\CacheTrait;
use Tiny\Traits\OrmTrait;

class Model extends _Model
{

    use OrmTrait, CacheTrait;

    /**
     * Get the database connection for the model.
     *
     * @return \Illuminate\Database\Connection
     */
    public function getConnection()
    {
        return DbHelper::initDb()->getConnection();
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string $column
     * @param  string $direction
     * @return $this
     */
    public static function orderBy($column, $direction = 'asc')
    {
        return self::_s_call('orderBy', [$column, $direction]);
    }

    /**
     * Find a model by its primary key.
     *
     * @param  mixed $id
     * @param  array $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null | stdClass
     */
    public static function find($id, $columns = ['*'])
    {
        return self::_s_call('find', [$id, $columns]);
    }

    /**
     * Find multiple models by their primary keys.
     *
     * @param  array $ids
     * @param  array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findMany($ids, $columns = ['*'])
    {
        return self::_s_call('findMany', [$ids, $columns]);
    }

    /**
     * Find a model by its primary key or throw an exception.
     *
     * @param  mixed $id
     * @param  array $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findOrFail($id, $columns = ['*'])
    {
        return self::_s_call('findOrFail', [$id, $columns]);
    }

    /**
     * Find a model by its primary key or return fresh model instance.
     *
     * @param  mixed $id
     * @param  array $columns
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function findOrNew($id, $columns = ['*'])
    {
        return self::_s_call('findOrNew', [$id, $columns]);
    }

    /**
     * Get the first record matching the attributes or instantiate it.
     *
     * @param  array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function firstOrNew(array $attributes)
    {
        return self::_s_call('firstOrNew', [$attributes]);
    }

    /**
     * Get the first record matching the attributes or create it.
     *
     * @param  array $attributes
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function firstOrCreate(array $attributes)
    {
        return self::_s_call('firstOrCreate', [$attributes]);
    }

    /**
     * Create or update a record matching the attributes, and fill it with values.
     *
     * @param  array $attributes
     * @param  array $values
     * @return \Illuminate\Database\Eloquent\Model
     */
    public static function updateOrCreate(array $attributes, array $values = [])
    {
        return self::_s_call('updateOrCreate', [$attributes, $values]);
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array $columns
     * @return \Illuminate\Database\Eloquent\Model|static|null | stdClass
     */
    public static function first($columns = ['*'])
    {
        return self::_s_call('first', [$columns]);

    }

    /**
     * Execute the query and get the first result or throw an exception.
     *
     * @param  array $columns
     * @return \Illuminate\Database\Eloquent\Model|static
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function firstOrFail($columns = ['*'])
    {
        return self::_s_call('firstOrFail', [$columns]);
    }

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array $columns
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function get($columns = ['*'])
    {
        return self::_s_call('get', [$columns]);
    }

    /**
     * Get a single column's value from the first result of a query.
     *
     * @param  string $column
     * @return mixed
     */
    public static function value($column)
    {
        return self::_s_call('value', [$column]);
    }

    /**
     * Chunk the results of the query.
     *
     * @param  int $count
     * @param  callable $callback
     * @return bool
     */
    public static function chunk($count, callable $callback)
    {
        return self::_s_call('chunk', [$count, $callback]);
    }

    /**
     * Chunk the results of a query by comparing numeric IDs.
     *
     * @param  int $count
     * @param  callable $callback
     * @param  string $column
     * @return bool
     */
    public static function chunkById($count, callable $callback, $column = 'id')
    {
        return self::_s_call('chunkById', [$count, $callback, $column]);
    }

    /**
     * Execute a callback over each item while chunking.
     *
     * @param  callable $callback
     * @param  int $count
     * @return bool
     */
    public static function each(callable $callback, $count = 1000)
    {
        return self::_s_call('each', [$callback, $count]);
    }

    /**
     * Get an array with the values of a given column.
     *
     * @param  string $column
     * @param  string|null $key
     * @return \Illuminate\Support\Collection
     */
    public static function pluck($column, $key = null)
    {
        return self::_s_call('pluck', [$column, $key]);
    }

    /**
     * Alias for the "pluck" method.
     *
     * @param  string $column
     * @param  string $key
     * @return \Illuminate\Support\Collection
     *
     * @deprecated since version 5.2. Use the "pluck" method directly.
     */
    public static function lists($column, $key = null)
    {
        return self::_s_call('lists', [$column, $key]);
    }

    /**
     * Paginate the given query.
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * @throws \InvalidArgumentException
     */
    public static function paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return self::_s_call('paginate', [$perPage, $columns, $pageName, $page]);
    }

    /**
     * Paginate the given query into a simple paginator.
     *
     * @param  int $perPage
     * @param  array $columns
     * @param  string $pageName
     * @param  int|null $page
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    public static function simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
    {
        return self::_s_call('simplePaginate', [$perPage, $columns, $pageName, $page]);
    }

    /**
     * Register a replacement for the default delete function.
     *
     * @param  \Closure $callback
     * @return void
     */
    public static function onDelete(Closure $callback)
    {
        self::_s_call('onDelete', [$callback]);
    }


    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param  bool $value
     * @param  \Closure $callback
     * @return $this
     */
    public static function when($value, $callback)
    {
        return self::_s_call('when', [$value, $callback]);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return $this
     */
    public static function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        return self::_s_call('where', [$column, $operator, $value, $boolean]);

    }

    /**
     * Add an "or where" clause to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  mixed $value
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function orWhere($column, $operator = null, $value = null)
    {
        return self::_s_call('orWhere', [$column, $operator, $value]);
    }

    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param  string $relation
     * @param  string $operator
     * @param  int $count
     * @param  string $boolean
     * @param  \Closure|null $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function has($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
    {
        return self::_s_call('has', [$relation, $operator, $count, $boolean, $callback]);
    }

    /**
     * Add a relationship count / exists condition to the query.
     *
     * @param  string $relation
     * @param  string $boolean
     * @param  \Closure|null $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function doesntHave($relation, $boolean = 'and', Closure $callback = null)
    {
        return self::_s_call('doesntHave', [$relation, $boolean, $callback]);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  string $relation
     * @param  \Closure $callback
     * @param  string $operator
     * @param  int $count
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function whereHas($relation, Closure $callback, $operator = '>=', $count = 1)
    {
        return self::_s_call('whereHas', [$relation, $callback, $operator, $count]);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses.
     *
     * @param  string $relation
     * @param  \Closure|null $callback
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function whereDoesntHave($relation, Closure $callback = null)
    {
        return self::_s_call('whereDoesntHave', [$relation, $callback]);
    }

    /**
     * Add a relationship count / exists condition to the query with an "or".
     *
     * @param  string $relation
     * @param  string $operator
     * @param  int $count
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function orHas($relation, $operator = '>=', $count = 1)
    {
        return self::_s_call('orHas', [$relation, $operator, $count]);
    }

    /**
     * Add a relationship count / exists condition to the query with where clauses and an "or".
     *
     * @param  string $relation
     * @param  \Closure $callback
     * @param  string $operator
     * @param  int $count
     * @return \Illuminate\Database\Eloquent\Builder|static
     */
    public static function orWhereHas($relation, Closure $callback, $operator = '>=', $count = 1)
    {
        return self::_s_call('orWhereHas', [$relation, $callback, $operator, $count]);
    }

    /**
     * Prevent the specified relations from being eager loaded.
     *
     * @param  mixed $relations
     * @return $this
     */
    public static function without($relations)
    {
        return self::_s_call('without', [$relations]);
    }

    /**
     * Add subselect queries to count the relations.
     *
     * @param  mixed $relations
     * @return $this
     */
    public static function withCount($relations)
    {
        return self::_s_call('withCount', [$relations]);
    }

    /**
     * Handle dynamic method calls into the model.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function _d_call($method, $parameters)
    {
        if (in_array($method, ['increment', 'decrement'])) {
            return call_user_func_array([$this, $method], $parameters);
        }

        $query = $this->newQuery();

        return call_user_func_array([$query, $method], $parameters);
    }

    private static function _s_call($method, $parameters)
    {
        $instance = new static();
        return $instance->_d_call($method, $parameters);
    }
}