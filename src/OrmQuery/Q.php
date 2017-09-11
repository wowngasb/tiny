<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/15 0015
 * Time: 14:59
 */

namespace Tiny\OrmQuery;

use Tiny\Func;

class where extends AbstractQuery
{
    public $operator = null;
    public $value = null;


    /**
     * where constructor.
     * @param mixed $value
     * @param string $operator
     * @param callable|null $filter 本条件是否生效的回调函数 参数为自身
     */
    public function __construct($value, $operator = '=', callable $filter = null)
    {
        $this->operator = !empty(self::$_allow_operator[$operator]) ? $operator : '=';
        $this->value = $value;

        parent::__construct($filter);
    }

    /**
     * @return array
     */
    protected function _queryArgs()
    {
        return [$this->operator, $this->value];
    }
}

class orWhere extends where
{
}

class whereNull extends AbstractQuery
{

    /**
     * @return array  返回 $query格式的数组  表示查询参数数组
     */
    protected function _queryArgs()
    {
        return [];
    }
}

class orWhereNull extends whereNull
{
}

class whereNotNull extends whereNull
{
}

class orWhereNotNull extends whereNull
{
}

class whereBetween extends AbstractQuery
{
    public $lower = null;
    public $upper = null;

    /**
     * whereBetween constructor.
     * @param mixed $lower
     * @param mixed $upper
     * @param callable|null $filter 本条件是否生效的回调函数 参数为自身
     */
    public function __construct($lower, $upper, callable $filter = null)
    {
        $this->lower = $lower;
        $this->upper = $upper;

        parent::__construct($filter);
    }

    /**
     * @return array  返回 $query格式的数组  表示查询参数数组
     */
    protected function _queryArgs()
    {
        $values = [$this->lower, $this->upper];
        return [$values,];
    }
}

class orWhereBetween extends whereBetween
{
}

class whereNotBetween extends whereBetween
{
}

class orWhereNotBetween extends whereBetween
{
}

class whereIn extends AbstractQuery
{
    public $values = null;

    /**
     * whereBetween constructor.
     * @param array $values
     * @param callable|null $filter 本条件是否生效的回调函数 参数为自身
     */
    public function __construct(array $values, callable $filter = null)
    {
        $this->values = $values;

        parent::__construct($filter);
    }

    /**
     * @return array  返回 $query格式的数组  表示查询参数数组
     */
    protected function _queryArgs()
    {
        return [$this->values];
    }
}

class whereNotIn extends whereIn
{
}

class orWhereIn extends whereIn
{
}

class orWhereNotIn extends whereIn
{
}

class whereColumn extends AbstractQuery
{

    public $first = null;
    public $second = null;
    public $operator = null;

    /**
     * whereBetween constructor.
     * @param string $first
     * @param string $second
     * @param string $operator
     * @param callable|null $filter 本条件是否生效的回调函数 参数为自身
     */
    public function __construct($first, $second, $operator = '=', callable $filter = null)
    {
        $this->first = $first;
        $this->second = $second;
        $this->operator = !empty(self::$_allow_operator[$operator]) ? $operator : '=';

        parent::__construct($filter);
    }

    /**
     * @return array  返回 $query格式的数组  表示查询参数数组
     */
    protected function _queryArgs()
    {
        return [$this->first, $this->operator, $this->second];
    }

}

class whereTime extends where
{
}

class orWhereTime extends where
{
}

class whereDate extends where
{
}

class orWhereDate extends where
{
}

class whereDay extends where
{
}

class whereMonth extends where
{
}


class whereYear extends where
{
}

class Q
{
    public static function where($value, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function orWhere($value, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereNull(callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function orWhereNull(callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereNotNull(callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function orWhereNotNull(callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereBetween($lower, $upper, callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function orWhereBetween($lower, $upper, callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereNotBetween($lower, $upper, callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function orWhereNotBetween($lower, $upper, callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereIn(array $values, callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function orWhereIn(array $values, callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereNotIn(array $values, callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function orWhereNotIn(array $values, callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereColumn($first, $second, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereTime($value, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function orWhereTime($value, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereDate($value, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function orWhereDate($value, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereDay($value, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereMonth($value, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

    public static function whereYear($value, $operator = '=', callable $filter = null)
    {
        return call_user_func_array(Func::method2name(__METHOD__), func_get_args());
    }

}