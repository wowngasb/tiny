<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/6/15 0015
 * Time: 14:59
 */

namespace Tiny\OrmQuery;


class Q
{
    public static function where($value, $operator = '=', callable $filter = null)
    {
        return new where($value, $operator, $filter);
    }

    public static function whereQ(callable $qFunc, callable  $filter = null){
        return new whereQ($qFunc, $filter);
    }

    public static function orWhereQ(callable $qFunc, callable  $filter = null){
        return new orWhereQ($qFunc, $filter);
    }

    public static function orWhere($value, $operator = '=', callable $filter = null)
    {
        return new orWhere($value, $operator, $filter);
    }

    public static function whereNull(callable $filter = null)
    {
        return new whereNull($filter);
    }

    public static function orWhereNull(callable $filter = null)
    {
        return new orWhereNull($filter);
    }

    public static function whereNotNull(callable $filter = null)
    {
        return new whereNotNull($filter);
    }

    public static function orWhereNotNull(callable $filter = null)
    {
        return new orWhereNotNull($filter);
    }

    public static function whereBetween($lower, $upper, callable $filter = null)
    {
        return new whereBetween($lower, $upper, $filter);
    }

    public static function orWhereBetween($lower, $upper, callable $filter = null)
    {
        return new orWhereBetween($lower, $upper, $filter);
    }

    public static function whereNotBetween($lower, $upper, callable $filter = null)
    {
        return new whereNotBetween($lower, $upper, $filter);
    }

    public static function orWhereNotBetween($lower, $upper, callable $filter = null)
    {
        return new orWhereNotBetween($lower, $upper, $filter);
    }

    public static function whereIn($values, callable $filter = null)
    {
        return new whereIn($values, $filter);
    }

    public static function orWhereIn($values, callable $filter = null)
    {
        return new orWhereIn($values, $filter);
    }

    public static function whereNotIn($values, callable $filter = null)
    {
        return new whereNotIn($values, $filter);
    }

    public static function orWhereNotIn($values, callable $filter = null)
    {
        return new orWhereNotIn($values, $filter);
    }

    public static function whereColumn($first, $second, $operator = '=', callable $filter = null)
    {
        return new whereColumn($first, $second, $operator, $filter);
    }

    public static function whereTime($value, $operator = '=', callable $filter = null)
    {
        return new whereTime($value, $operator, $filter);
    }

    public static function orWhereTime($value, $operator = '=', callable $filter = null)
    {
        return new orWhereTime($value, $operator, $filter);
    }

    public static function whereDate($value, $operator = '=', callable $filter = null)
    {
        return new whereDate($value, $operator, $filter);
    }

    public static function orWhereDate($value, $operator = '=', callable $filter = null)
    {
        return new orWhereDate($value, $operator, $filter);
    }

    public static function whereDay($value, $operator = '=', callable $filter = null)
    {
        return new whereDay($value, $operator, $filter);
    }

    public static function whereMonth($value, $operator = '=', callable $filter = null)
    {
        return new whereMonth($value, $operator, $filter);
    }

    public static function whereYear($value, $operator = '=', callable $filter = null)
    {
        return new whereYear($value, $operator, $filter);
    }

}