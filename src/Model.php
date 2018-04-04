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
use Tiny\Traits\LogTrait;
use Tiny\Traits\OrmTrait;

class Model extends _Model
{

    use OrmTrait, LogTrait;

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
     * Set the columns to be selected.
     *
     * @param  array|mixed $columns
     * @return \Illuminate\Database\Query\Builder
     */
    public static function select($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        return self::_s_call('select', [$columns]);
    }

    /**
     * Add a new "raw" select expression to the query.
     *
     * @param  string $expression
     * @param  array $bindings
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function selectRaw($expression, array $bindings = [])
    {
        return self::_s_call('selectRaw', [$expression, $bindings]);
    }

    /**
     * Add a subselect expression to the query.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string $query
     * @param  string $as
     * @return \Illuminate\Database\Query\Builder|static
     *
     * @throws \InvalidArgumentException
     */
    public static function selectSub($query, $as)
    {
        return self::_s_call('selectSub', [$query, $as]);
    }

    /**
     * Add a new select column to the query.
     *
     * @param  array|mixed $column
     * @return \Illuminate\Database\Query\Builder
     */
    public static function addSelect($column)
    {
        return self::_s_call('addSelect', [$column]);
    }

    /**
     * Force the query to only return distinct results.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public static function distinct()
    {
        return self::_s_call('distinct', []);
    }

    /**
     * Apply the callback's query changes if the given "value" is true.
     *
     * @param  bool $value
     * @param  \Closure $callback
     * @return \Illuminate\Database\Query\Builder
     */
    public static function when($value, $callback)
    {
        return self::_s_call('when', [$value, $callback]);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|array|\Closure $column
     * @param  string $operator
     * @param  mixed $value
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder | \Illuminate\Database\Eloquent\Builder
     *
     * @throws \InvalidArgumentException
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
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhere($column, $operator = null, $value = null)
    {
        return self::_s_call('orWhere', [$column, $operator, $value]);
    }

    /**
     * Add a "where" clause comparing two columns to the query.
     *
     * @param  string|array $first
     * @param  string|null $operator
     * @param  string|null $second
     * @param  string|null $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereColumn($first, $operator = null, $second = null, $boolean = 'and')
    {
        return self::_s_call('whereColumn', [$first, $operator, $second, $boolean]);
    }

    /**
     * Add an "or where" clause comparing two columns to the query.
     *
     * @param  string|array $first
     * @param  string|null $operator
     * @param  string|null $second
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereColumn($first, $operator = null, $second = null)
    {
        return self::_s_call('orWhereColumn', [$first, $operator, $second]);
    }

    /**
     * Add a raw where clause to the query.
     *
     * @param  string $sql
     * @param  array $bindings
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder
     */
    public static function whereRaw($sql, array $bindings = [], $boolean = 'and')
    {
        return self::_s_call('whereRaw', [$sql, $bindings, $boolean]);
    }

    /**
     * Add a raw or where clause to the query.
     *
     * @param  string $sql
     * @param  array $bindings
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereRaw($sql, array $bindings = [])
    {
        return self::_s_call('orWhereRaw', [$sql, $bindings]);
    }

    /**
     * Add a where between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Database\Query\Builder
     */
    public static function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        return self::_s_call('whereBetween', [$column, $values, $boolean, $not]);
    }

    /**
     * Add an or where between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereBetween($column, array $values)
    {
        return self::_s_call('orWhereBetween', [$column, $values]);
    }

    /**
     * Add a where not between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereNotBetween($column, array $values, $boolean = 'and')
    {
        return self::_s_call('whereNotBetween', [$column, $values, $boolean]);
    }

    /**
     * Add an or where not between statement to the query.
     *
     * @param  string $column
     * @param  array $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereNotBetween($column, array $values)
    {
        return self::_s_call('orderBy', [$column, $values]);
    }

    /**
     * Add a nested where statement to the query.
     *
     * @param  \Closure $callback
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereNested(Closure $callback, $boolean = 'and')
    {
        return self::_s_call('orWhereNotBetween', [$callback, $boolean]);
    }

    /**
     * Create a new query instance for nested where condition.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public static function forNestedWhere()
    {
        return self::_s_call('forNestedWhere', []);
    }

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param  \Illuminate\Database\Query\Builder|static $query
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder
     */
    public static function addNestedWhereQuery($query, $boolean = 'and')
    {
        return self::_s_call('addNestedWhereQuery', [$query, $boolean]);
    }

    /**
     * Add an exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Database\Query\Builder
     */
    public static function whereExists(Closure $callback, $boolean = 'and', $not = false)
    {
        return self::_s_call('whereExists', [$callback, $boolean, $not]);
    }

    /**
     * Add an or exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  bool $not
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereExists(Closure $callback, $not = false)
    {
        return self::_s_call('orWhereExists', [$callback, $not]);
    }

    /**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure $callback
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereNotExists(Closure $callback, $boolean = 'and')
    {
        return self::_s_call('whereNotExists', [$callback, $boolean]);
    }

    /**
     * Add a where not exists clause to the query.
     *
     * @param  \Closure $callback
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereNotExists(Closure $callback)
    {
        return self::_s_call('orWhereNotExists', [$callback]);
    }

    /**
     * Add an exists clause to the query.
     *
     * @param  mixed $query
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Database\Query\Builder | \Illuminate\Database\Eloquent\Builder
     */
    public static function addWhereExistsQuery($query, $boolean = 'and', $not = false)
    {
        return self::_s_call('addWhereExistsQuery', [$query, $boolean, $not]);
    }

    /**
     * Add a "where in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Database\Query\Builder
     */
    public static function whereIn($column, $values, $boolean = 'and', $not = false)
    {
        return self::_s_call('whereIn', [$column, $values, $boolean, $not]);
    }

    /**
     * Add an "or where in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereIn($column, $values)
    {
        return self::_s_call('orWhereIn', [$column, $values]);
    }

    /**
     * Add a "where not in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereNotIn($column, $values, $boolean = 'and')
    {
        return self::_s_call('whereNotIn', [$column, $values, $boolean]);
    }

    /**
     * Add an "or where not in" clause to the query.
     *
     * @param  string $column
     * @param  mixed $values
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereNotIn($column, $values)
    {
        return self::_s_call('orWhereNotIn', [$column, $values]);
    }

    /**
     * Add a "where null" clause to the query.
     *
     * @param  string $column
     * @param  string $boolean
     * @param  bool $not
     * @return \Illuminate\Database\Query\Builder
     */
    public static function whereNull($column, $boolean = 'and', $not = false)
    {
        return self::_s_call('whereNull', [$column, $boolean, $not]);
    }

    /**
     * Add an "or where null" clause to the query.
     *
     * @param  string $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereNull($column)
    {
        return self::_s_call('orWhereNull', [$column]);
    }

    /**
     * Add a "where not null" clause to the query.
     *
     * @param  string $column
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereNotNull($column, $boolean = 'and')
    {
        return self::_s_call('ordwhereNotNullerBy', [$column, $boolean]);
    }

    /**
     * Add an "or where not null" clause to the query.
     *
     * @param  string $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereNotNull($column)
    {
        return self::_s_call('orWhereNotNull', [$column]);
    }

    /**
     * Add a "where date" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereDate($column, $operator, $value, $boolean = 'and')
    {
        return self::_s_call('whereDate', [$column, $operator, $value, $boolean]);
    }

    /**
     * Add an "or where date" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereDate($column, $operator, $value)
    {
        return self::_s_call('orWhereDate', [$column, $operator, $value]);
    }

    /**
     * Add a "where time" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereTime($column, $operator, $value, $boolean = 'and')
    {
        return self::_s_call('whereTime', [$column, $operator, $value, $boolean]);
    }

    /**
     * Add an "or where time" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereTime($column, $operator, $value)
    {
        return self::_s_call('orWhereTime', [$column, $operator, $value]);
    }

    /**
     * Add a "where day" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereDay($column, $operator, $value, $boolean = 'and')
    {
        return self::_s_call('whereDay', [$column, $operator, $value, $boolean]);
    }

    /**
     * Add a "where month" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereMonth($column, $operator, $value, $boolean = 'and')
    {
        return self::_s_call('whereMonth', [$column, $operator, $value, $boolean]);
    }

    /**
     * Add a "where year" statement to the query.
     *
     * @param  string $column
     * @param  string $operator
     * @param  int $value
     * @param  string $boolean
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function whereYear($column, $operator, $value, $boolean = 'and')
    {
        return self::_s_call('whereYear', [$column, $operator, $value, $boolean]);
    }

    /**
     * Handles dynamic "where" clauses to the query.
     *
     * @param  string $method
     * @param  string $parameters
     * @return \Illuminate\Database\Query\Builder
     */
    public static function dynamicWhere($method, $parameters)
    {
        return self::_s_call('dynamicWhere', [$method, $parameters]);
    }

    /**
     * Add an "order by" clause to the query.
     *
     * @param  string $column
     * @param  string $direction
     * @return \Illuminate\Database\Query\Builder | \Illuminate\Database\Eloquent\Builder
     */
    public static function orderBy($column, $direction = 'asc')
    {
        return self::_s_call('orderBy', [$column, $direction]);
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function latest($column = 'created_at')
    {
        return self::_s_call('latest', [$column]);
    }

    /**
     * Add an "order by" clause for a timestamp to the query.
     *
     * @param  string $column
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function oldest($column = 'created_at')
    {
        return self::_s_call('oldest', [$column]);
    }

    /**
     * Put the query's results in random order.
     *
     * @param  string $seed
     * @return \Illuminate\Database\Query\Builder
     */
    public static function inRandomOrder($seed = '')
    {
        return self::_s_call('inRandomOrder', [$seed]);
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
     * Concatenate values of a given column as a string.
     *
     * @param  string $column
     * @param  string $glue
     * @return string
     */
    public static function implode($column, $glue = '')
    {
        return self::_s_call('implode', [$column, $glue]);
    }

    /**
     * Determine if any rows exist for the current query.
     *
     * @return bool
     */
    public static function exists()
    {
        return self::_s_call('exists', []);
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param  string $columns
     * @return int
     */
    public static function count($columns = '*')
    {
        return self::_s_call('count', [$columns]);
    }

    /**
     * Retrieve the minimum value of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public static function min($column)
    {
        return self::_s_call('min', [$column]);
    }

    /**
     * Retrieve the maximum value of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public static function max($column)
    {
        return self::_s_call('max', [$column]);
    }

    /**
     * Retrieve the sum of the values of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public static function sum($column)
    {
        return self::_s_call('sum', [$column]);
    }

    /**
     * Retrieve the average of the values of a given column.
     *
     * @param  string $column
     * @return mixed
     */
    public static function avg($column)
    {
        return self::_s_call('avg', [$column]);
    }

    /**
     * Alias for the "avg" method.
     *
     * @param  string $column
     * @return mixed
     */
    public static function average($column)
    {
        return self::_s_call('average', [$column]);
    }

    /**
     * Execute an aggregate function on the database.
     *
     * @param  string $function
     * @param  array $columns
     * @return mixed
     */
    public static function aggregate($function, $columns = ['*'])
    {
        return self::_s_call('aggregate', [$function, $columns]);
    }

    /**
     * Execute a numeric aggregate function on the database.
     *
     * @param  string $function
     * @param  array $columns
     * @return float|int
     */
    public static function numericAggregate($function, $columns = ['*'])
    {
        return self::_s_call('numericAggregate', [$function, $columns]);
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
     * Add a relationship count / exists condition to the query.
     *
     * @param  string $relation
     * @param  string $operator
     * @param  int $count
     * @param  string $boolean
     * @param  \Closure|null $callback
     * @return \Illuminate\Database\Query\Builder|static
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
     * @return \Illuminate\Database\Query\Builder|static
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
     * @return \Illuminate\Database\Query\Builder|static
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
     * @return \Illuminate\Database\Query\Builder|static
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
     * @return \Illuminate\Database\Query\Builder|static
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
     * @return \Illuminate\Database\Query\Builder|static
     */
    public static function orWhereHas($relation, Closure $callback, $operator = '>=', $count = 1)
    {
        return self::_s_call('orWhereHas', [$relation, $callback, $operator, $count]);
    }

    /**
     * Prevent the specified relations from being eager loaded.
     *
     * @param  mixed $relations
     * @return \Illuminate\Database\Query\Builder
     */
    public static function without($relations)
    {
        return self::_s_call('without', [$relations]);
    }

    /**
     * Add subselect queries to count the relations.
     *
     * @param  mixed $relations
     * @return \Illuminate\Database\Query\Builder
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