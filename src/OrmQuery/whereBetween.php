<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2017/9/29
 * Time: 14:54
 */

namespace Tiny\OrmQuery;


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
